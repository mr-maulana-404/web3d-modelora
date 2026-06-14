<?php

namespace App\Services\GlbTextureEnhancement;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\Steps\AnalyzeGlbStep;
use App\Services\GlbTextureEnhancement\Steps\EnhanceTexturesStep;
use App\Services\GlbTextureEnhancement\Steps\ExportEnhancedGlbStep;
use App\Services\GlbTextureEnhancement\Steps\ExtractTexturesStep;
use App\Services\GlbTextureEnhancement\Steps\GeneratePreviewStep;
use App\Services\GlbTextureEnhancement\Steps\RebindEnhancedTexturesStep;

class GlbTextureEnhancementPipelineService
{
    public function __construct(
        private readonly AnalyzeGlbStep $analyzeGlbStep,
        private readonly ExtractTexturesStep $extractTexturesStep,
        private readonly EnhanceTexturesStep $enhanceTexturesStep,
        private readonly RebindEnhancedTexturesStep $rebindEnhancedTexturesStep,
        private readonly ExportEnhancedGlbStep $exportEnhancedGlbStep,
        private readonly GeneratePreviewStep $generatePreviewStep,
    ) {
    }

    public function process(GlbTextureEnhancementProject $project): void
    {
        $workspace = new GlbTextureEnhancementWorkspace($project->fresh());

        try {
            $this->assertPipelineEnabled();

            $workspace->ensureDirectories();
            $workspace->ensureWritable();
            $workspace->syncInputModelFromPublicDisk();

            $this->transitionStage($project, 'analyzing', 10);
            $analysis = $this->analyzeGlbStep->handle($project->fresh(), $workspace);
            $this->appendProjectLog($project, sprintf(
                'Analyze complete: meshes=%d, materials=%d, textures=%d, uv_present=%s.',
                $analysis['mesh_count'] ?? 0,
                $analysis['material_count'] ?? 0,
                $analysis['texture_count'] ?? 0,
                ($analysis['uv_present'] ?? false) ? 'yes' : 'no'
            ));
            $this->appendWarnings($project, $analysis['warnings'] ?? []);

            $this->transitionStage($project, 'extracting_textures', 30);
            $originalManifest = $this->extractTexturesStep->handle($project->fresh(), $workspace);
            $this->appendProjectLog($project, sprintf(
                'Texture extraction complete: %d texture bindings exported.',
                $originalManifest['texture_count'] ?? 0
            ));
            $this->appendWarnings($project, $originalManifest['warnings'] ?? []);

            $this->transitionStage($project, 'enhancing_textures', 55);
            $enhancedManifest = $this->enhanceTexturesStep->handle($project->fresh(), $workspace);
            $this->appendProjectLog($project, sprintf(
                'Texture enhancement complete: %d enhanced textures generated.',
                $enhancedManifest['texture_count'] ?? 0
            ));
            $this->appendWarnings($project, $enhancedManifest['warnings'] ?? []);

            $this->transitionStage($project, 'rebinding_materials', 72);
            $this->rebindEnhancedTexturesStep->handle($project->fresh(), $workspace);
            $this->appendProjectLog($project, 'Enhanced textures rebound into Blender working scene.');

            $this->transitionStage($project, 'exporting_glb', 86);
            $relativeOutputPath = $this->exportEnhancedGlbStep->handle($project->fresh(), $workspace);
            $this->appendProjectLog($project, 'Enhanced GLB exported to '.$relativeOutputPath.'.');

            $this->transitionStage($project, 'generating_preview', 93);
            try {
                $previewPath = $this->generatePreviewStep->handle($project->fresh(), $workspace);
                if ($previewPath) {
                    $this->appendProjectLog($project, 'Preview image generated at '.$previewPath.'.');
                }
            } catch (\Throwable $previewException) {
                $this->appendProjectLog($project, 'Preview warning: '.$previewException->getMessage());
            }

            $project->fresh()->forceFill([
                'status' => 'ready',
                'pipeline_stage' => 'ready',
                'progress' => 100,
                'error_message' => null,
                'published_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            $this->appendProjectLog($project, 'Pipeline failed: '.$exception->getMessage());
            throw $exception;
        }
    }

    private function assertPipelineEnabled(): void
    {
        if (! config('glb_texture_enhancement.enable_pipeline', true)) {
            throw new \RuntimeException('GLB texture enhancement pipeline is disabled by configuration.');
        }
    }

    private function transitionStage(GlbTextureEnhancementProject $project, string $stage, int $progress): void
    {
        $project->fresh()->forceFill([
            'status' => 'processing',
            'pipeline_stage' => $stage,
            'progress' => $progress,
            'error_message' => null,
        ])->save();

        $this->appendProjectLog($project, sprintf('Stage changed to %s (%d%%).', $stage, $progress));
    }

    private function appendWarnings(GlbTextureEnhancementProject $project, array $warnings): void
    {
        foreach ($warnings as $warning) {
            $this->appendProjectLog($project, 'Warning: '.$warning);
        }
    }

    private function appendProjectLog(GlbTextureEnhancementProject $project, string $message): void
    {
        $project->fresh()->appendProcessingLog($message);
    }
}
