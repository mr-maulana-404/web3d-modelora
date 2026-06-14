<?php

namespace App\Jobs;

use App\Models\GlbTextureEnhancementProject;
use App\Models\Wallet;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementPipelineService;
use App\Services\GlbTextureEnhancement\MeshyEnhancementPipelineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessGlbTextureEnhancementProject implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    private const MESHY_COST = 20;

    public function __construct(
        public readonly int $projectId,
    ) {
    }

    public function handle(
        GlbTextureEnhancementPipelineService $pipelineService,
        MeshyEnhancementPipelineService $meshyPipelineService,
    ): void {
        $project = GlbTextureEnhancementProject::find($this->projectId);

        if (! $project) {
            return;
        }

        if (! $project->input_glb_path) {
            throw new \RuntimeException('Input model path is empty. Upload a GLB or ZIP GLTF file before starting enhancement.');
        }

        try {
            $project->forceFill([
                'status' => 'processing',
                'pipeline_stage' => $project->pipeline_stage ?: 'uploaded',
                'error_message' => null,
            ])->save();

            if (($project->enhancement_options['mode'] ?? 'local_texture') === 'meshy_repair_retexture') {
                $meshyPipelineService->process($project);
            } else {
                $pipelineService->process($project);
            }
        } catch (\Throwable $exception) {
            $this->refundIfApplicable($project);
            $this->markAsFailed($project, $exception->getMessage());

            throw $exception;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $project = GlbTextureEnhancementProject::find($this->projectId);
        $message = $exception?->getMessage() ?: 'Queue worker reported an unknown failure for this GLB enhancement project.';

        if (! $project || $project->status === 'ready') {
            return;
        }

        if ($project->status === 'failed' && $project->error_message === $message) {
            return;
        }

        $this->refundIfApplicable($project);
        $this->markAsFailed($project, $message);
    }

    private function markAsFailed(GlbTextureEnhancementProject $project, string $message): void
    {
        $project->forceFill([
            'status' => 'failed',
            'pipeline_stage' => 'failed',
            'error_message' => $message,
        ])->save();

        $project->appendProcessingLog('Job failed: '.$message);
    }

    private function refundIfApplicable(GlbTextureEnhancementProject $project): void
    {
        $mode = $project->enhancement_options['mode'] ?? 'local_texture';

        if ($mode !== 'meshy_repair_retexture') {
            return;
        }

        $user = $project->user;

        if (! $user || $user->usertype === 'admin') {
            return;
        }

        DB::transaction(function () use ($user) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                $wallet->increment('credits', self::MESHY_COST);
            }
        });

        $project->appendProcessingLog('Credits refunded ('.self::MESHY_COST.') due to enhancement failure.');
    }
}