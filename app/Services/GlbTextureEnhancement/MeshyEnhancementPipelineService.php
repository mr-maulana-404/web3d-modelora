<?php

namespace App\Services\GlbTextureEnhancement;

use App\Models\GlbTextureEnhancementProject;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MeshyEnhancementPipelineService
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function process(GlbTextureEnhancementProject $project): void
    {
        $workspace = new GlbTextureEnhancementWorkspace($project->fresh());

        try {
            $workspace->ensureDirectories();
            $workspace->ensureWritable();
            $workspace->syncInputModelFromPublicDisk();

            $this->transitionStage($project, 'preparing_input', 8);
            $inputGlbPath = $this->prepareInputGlb($workspace);
            $this->appendProjectLog($project, 'Prepared GLB input for Meshy: '.$inputGlbPath.'.');

            $this->transitionStage($project, 'meshy_repair_submitting', 15);
            $repairId = $this->createRepairTask($inputGlbPath);
            $this->rememberMeshyTask($project, 'repair_task_id', $repairId);
            $this->appendProjectLog($project, 'Meshy repair task created: '.$repairId.'.');

            $repairTask = $this->pollTask($project, 'repair', $repairId, 20, 50);
            $repairModelUrl = $this->extractModelUrl($repairTask, 'repair');
            $this->appendProjectLog($project, 'Meshy repair completed.');

            $this->transitionStage($project, 'meshy_retexture_submitting', 55);
            $retextureId = $this->createRetextureTask($project, $repairModelUrl);
            $this->rememberMeshyTask($project, 'retexture_task_id', $retextureId);
            $this->appendProjectLog($project, 'Meshy retexture task created: '.$retextureId.'.');

            $retextureTask = $this->pollTask($project, 'retexture', $retextureId, 60, 92);
            $finalModelUrl = $this->extractModelUrl($retextureTask, 'retexture');

            $this->transitionStage($project, 'downloading_output', 96);
            $this->downloadToPublicDisk($finalModelUrl, $workspace->publicEnhancedGlbRelativePath());

            if (! empty($retextureTask['thumbnail_url'])) {
                $this->downloadToPublicDisk($retextureTask['thumbnail_url'], $workspace->publicPreviewRelativePath());
            } elseif (! empty($repairTask['thumbnail_url'])) {
                $this->downloadToPublicDisk($repairTask['thumbnail_url'], $workspace->publicPreviewRelativePath());
            }

            $project->fresh()->forceFill([
                'status' => 'ready',
                'pipeline_stage' => 'ready',
                'progress' => 100,
                'output_glb_path' => $workspace->publicEnhancedGlbRelativePath(),
                'preview_image' => Storage::disk('public')->exists($workspace->publicPreviewRelativePath())
                    ? $workspace->publicPreviewRelativePath()
                    : null,
                'error_message' => null,
                'published_at' => now(),
            ])->save();

            $this->appendProjectLog($project, 'Meshy output downloaded to '.$workspace->publicEnhancedGlbRelativePath().'.');
        } catch (\Throwable $exception) {
            $this->appendProjectLog($project, 'Meshy pipeline failed: '.$exception->getMessage());
            throw $exception;
        }
    }

    private function prepareInputGlb(GlbTextureEnhancementWorkspace $workspace): string
    {
        $inputPath = $workspace->runtimeInputModelPath();

        if (strtolower(pathinfo($inputPath, PATHINFO_EXTENSION)) === 'glb') {
            return $inputPath;
        }

        $outputPath = $workspace->meshyInputGlbPath();
        $logPath = $workspace->logPath('meshy_prepare_input.log');

        $script = <<<PY
import bpy
import json
import os
import traceback

INPUT_MODEL = {$this->pythonLiteral($inputPath)}
OUTPUT_GLB = {$this->pythonLiteral($outputPath)}

try:
    if not os.path.exists(INPUT_MODEL):
        raise RuntimeError(f"Input model is missing: {INPUT_MODEL}")

    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=INPUT_MODEL)
    os.makedirs(os.path.dirname(OUTPUT_GLB), exist_ok=True)
    bpy.ops.export_scene.gltf(
        filepath=OUTPUT_GLB,
        export_format="GLB",
        check_existing=False,
        export_image_format="AUTO",
    )

    if not os.path.exists(OUTPUT_GLB) or os.path.getsize(OUTPUT_GLB) == 0:
        raise RuntimeError("GLTF to GLB conversion did not create a valid output file.")

    print(json.dumps({"output_glb": OUTPUT_GLB, "size": os.path.getsize(OUTPUT_GLB)}))
except Exception:
    traceback.print_exc()
    raise
PY;

        File::put($workspace->scriptPath('meshy_prepare_input.py'), $script);
        $this->blenderRunner->runScript($workspace->scriptPath('meshy_prepare_input.py'), $workspace->rootPath(), $logPath);

        return $outputPath;
    }

    private function createRepairTask(string $inputGlbPath): string
    {
        $response = $this->postJson('/print/repair', [
            'model_url' => $this->dataUri($inputGlbPath),
            'alpha_thumbnail' => true,
        ]);

        return $this->taskIdFromResponse($response, 'repair');
    }

    private function createRetextureTask(GlbTextureEnhancementProject $project, string $modelUrl): string
    {
        $options = $project->enhancement_options ?: [];
        $prompt = trim((string) ($options['text_style_prompt'] ?? ''));

        if ($prompt === '') {
            $prompt = (string) config('meshy.default_retexture_prompt');
        }

        $response = $this->postJson('/retexture', [
            'model_url' => $modelUrl,
            'text_style_prompt' => mb_substr($prompt, 0, 600),
            'ai_model' => 'latest',
            'enable_original_uv' => true,
            'enable_pbr' => true,
            'hd_texture' => true,
            'remove_lighting' => false,
            'target_formats' => ['glb'],
            'alpha_thumbnail' => true,
        ]);

        return $this->taskIdFromResponse($response, 'retexture');
    }

    private function pollTask(GlbTextureEnhancementProject $project, string $type, string $taskId, int $startProgress, int $endProgress): array
    {
        $deadline = time() + (int) config('meshy.max_wait_seconds', 1800);
        $path = $type === 'repair' ? '/print/repair/'.$taskId : '/retexture/'.$taskId;

        while (time() <= $deadline) {
            $task = $this->getJson($path);
            $status = strtoupper((string) ($task['status'] ?? ''));
            $remoteProgress = (int) ($task['progress'] ?? 0);
            $mappedProgress = $startProgress + (int) floor(($remoteProgress / 100) * max(0, $endProgress - $startProgress));

            $this->transitionStage($project, 'meshy_'.$type.'_'.strtolower($status ?: 'pending'), min($mappedProgress, $endProgress));

            if ($status === 'SUCCEEDED') {
                return $task;
            }

            if (in_array($status, ['FAILED', 'CANCELED'], true)) {
                $message = $task['task_error']['message'] ?? $type.' task failed.';
                throw new \RuntimeException('Meshy '.$type.' failed: '.$message);
            }

            sleep(max(2, (int) config('meshy.poll_interval', 8)));
        }

        throw new \RuntimeException('Meshy '.$type.' task timed out.');
    }

    private function extractModelUrl(array $task, string $type): string
    {
        $modelUrls = $task['model_urls'] ?? [];
        $url = $modelUrls['glb'] ?? '';

        if (! is_string($url) || trim($url) === '') {
            throw new \RuntimeException('Meshy '.$type.' task did not return a GLB output URL.');
        }

        return $url;
    }

    private function downloadToPublicDisk(string $url, string $relativePath): void
    {
        $response = Http::timeout((int) config('meshy.timeout', 120))->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to download Meshy output: HTTP '.$response->status());
        }

        Storage::disk('public')->put($relativePath, $response->body());
    }

    private function postJson(string $path, array $payload): array
    {
        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('meshy.timeout', 120))
            ->post($this->baseUrl().$path, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Meshy API request failed: HTTP '.$response->status().' '.$response->body());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('Meshy API returned a non-JSON response.');
        }

        return $json;
    }

    private function getJson(string $path): array
    {
        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout((int) config('meshy.timeout', 120))
            ->get($this->baseUrl().$path);

        if (! $response->successful()) {
            throw new \RuntimeException('Meshy API request failed: HTTP '.$response->status().' '.$response->body());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('Meshy API returned a non-JSON response.');
        }

        return $json;
    }

    private function taskIdFromResponse(array $response, string $type): string
    {
        $taskId = $response['result'] ?? null;

        if (! is_string($taskId) || trim($taskId) === '') {
            throw new \RuntimeException('Meshy '.$type.' task creation did not return a task id.');
        }

        return $taskId;
    }

    private function dataUri(string $path): string
    {
        return 'data:application/octet-stream;base64,'.base64_encode((string) File::get($path));
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) config('meshy.api_key'), "'\"");

        if ($apiKey === '') {
            throw new \RuntimeException('MESHY_API_KEY is not configured.');
        }

        return $apiKey;
    }

    private function baseUrl(): string
    {
        $endpoint = rtrim(trim((string) config('meshy.api_endpoint')), '/');

        if (str_ends_with($endpoint, '/openapi/v1')) {
            return $endpoint;
        }

        if (str_ends_with($endpoint, '/v1')) {
            return substr($endpoint, 0, -3).'/openapi/v1';
        }

        return $endpoint.'/openapi/v1';
    }

    private function transitionStage(GlbTextureEnhancementProject $project, string $stage, int $progress): void
    {
        $project->fresh()->forceFill([
            'status' => 'processing',
            'pipeline_stage' => $stage,
            'progress' => min(100, max(0, $progress)),
            'error_message' => null,
        ])->save();

        $this->appendProjectLog($project, sprintf('Stage changed to %s (%d%%).', $stage, min(100, max(0, $progress))));
    }

    private function rememberMeshyTask(GlbTextureEnhancementProject $project, string $key, string $taskId): void
    {
        $fresh = $project->fresh();
        $meta = $fresh->analysis_meta ?: [];
        $meta['meshy'][$key] = $taskId;

        $fresh->forceFill(['analysis_meta' => $meta])->save();
    }

    private function appendProjectLog(GlbTextureEnhancementProject $project, string $message): void
    {
        $project->fresh()->appendProcessingLog($message);
    }

    private function pythonLiteral(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}