<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Models\Model3D;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishEnhancedModelStep
{
    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): Model3D
    {
        if (! $project->output_glb_path) {
            throw new \RuntimeException('Output GLB path is empty, publishing cannot continue.');
        }

        $logPath = $workspace->logPath('publish.log');
        File::ensureDirectoryExists(dirname($logPath));

        $model = DB::transaction(function () use ($project) {
            $existingModel = $project->model3d;
            $slug = $this->generateUniqueSlug($project->name, $existingModel?->id);

            $attributes = [
                'user_id' => $project->user_id,
                'name' => $project->name,
                'slug' => $slug,
                'description' => $project->description,
                'model_path' => $project->output_glb_path,
                'model_format' => 'glb',
                'is_published' => true,
                'thumbnail_path' => $project->preview_image ?: $existingModel?->thumbnail_path,
                'processing_status' => 'ready',
                'source_project_id' => $project->id,
                'source_type' => 'glb_texture_enhancement',
            ];

            if ($existingModel) {
                $existingModel->update($attributes);
                $model = $existingModel->fresh();
            } else {
                $model = Model3D::create($attributes);
            }

            $project->forceFill([
                'model3d_id' => $model->id,
                'published_at' => now(),
            ])->save();

            return $model;
        });

        File::append($logPath, implode(PHP_EOL, [
            'timestamp: '.now()->toDateTimeString(),
            'command: internal publish',
            'exit_code: 0',
            'stdout:',
            sprintf(
                'Published model3d_id=%d path=%s preview=%s',
                $model->id,
                $model->model_path,
                $model->thumbnail_path ?? 'n/a'
            ),
            'stderr:',
            '',
            str_repeat('-', 80),
            '',
        ]));

        return $model;
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'glb-enhanced-model';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Model3D::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
