<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGlbTextureEnhancementProject;
use App\Models\GlbTextureEnhancementProject;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;
use RuntimeException;

class GlbTextureEnhancementController extends Controller
{
    private const MODE_LOCAL_TEXTURE = 'local_texture';
    private const MODE_MESHY_REPAIR_RETEXTURE = 'meshy_repair_retexture';
    private const MESHY_COST = 20;

    public function index()
    {
        $projects = GlbTextureEnhancementProject::query()
            ->where('user_id', Auth::id())
            ->where('pipeline_type', 'model_enhancement')
            ->latest()
            ->paginate(10);

        return view('app.gallery.enhancement', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_file' => ['required', 'file', 'max:102400'],
            'mode' => ['required', Rule::in([self::MODE_LOCAL_TEXTURE, self::MODE_MESHY_REPAIR_RETEXTURE])],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'text_style_prompt' => ['nullable', 'string', 'max:600'],
        ]);

        if ($validated['mode'] === self::MODE_MESHY_REPAIR_RETEXTURE) {
            $user = Auth::user();

            if ($user->usertype !== 'admin') {
                $this->chargeMeshyCredits($user);
            }
        }

        /** @var UploadedFile $file */
        $file = $validated['model_file'];
        $name = ($validated['name'] ?? null) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $project = GlbTextureEnhancementProject::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'pipeline_type' => 'model_enhancement',
            'enhancement_options' => $this->enhancementOptions($validated),
            'progress' => 0,
        ]);

        try {
            $relativePath = $this->storeUploadedModel($file, $project);
        } catch (\Throwable $exception) {
            $project->delete();

            throw $exception;
        }

        $project->forceFill([
            'input_glb_path' => $relativePath,
            'status' => 'uploaded',
            'pipeline_stage' => 'uploaded',
            'progress' => 0,
        ])->save();

        $project->appendProcessingLog('Model uploaded successfully to '.$relativePath.'.');
        $this->queueEnhancement($project);

        return redirect()
            ->route('gallery.enhancement.index')
            ->with('success', 'Model enhancement has been queued.');
    }

    public function destroy(GlbTextureEnhancementProject $project)
    {
        $project = $this->authorizeProject($project);
        $uuid = $project->uuid;

        $workspaceRoot = rtrim((string) config('glb_texture_enhancement.working_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$uuid;

        if (File::isDirectory($workspaceRoot)) {
            File::deleteDirectory($workspaceRoot);
        }

        $publicOutputDir = rtrim((string) config('glb_texture_enhancement.public_output_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$uuid;

        if (File::isDirectory($publicOutputDir)) {
            File::deleteDirectory($publicOutputDir);
        }

        $inputDir = Storage::disk('public')->path('enhancement_inputs'.DIRECTORY_SEPARATOR.$uuid);

        if (File::isDirectory($inputDir)) {
            File::deleteDirectory($inputDir);
        }

        $project->delete();

        return redirect()
            ->route('gallery.enhancement.index')
            ->with('success', 'Enhancement project has been deleted.');
    }

    public function poll(GlbTextureEnhancementProject $project)
    {
        $project = $this->authorizeProject($project)->fresh();

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
            'mode' => $project->enhancement_options['mode'] ?? self::MODE_LOCAL_TEXTURE,
            'status' => $project->status ?? 'awaiting_upload',
            'pipeline_stage' => $project->pipeline_stage,
            'progress' => $project->progress,
            'error_message' => $project->error_message,
            'input_glb_path' => $project->input_glb_path,
            'output_glb_path' => $project->output_glb_path,
            'preview_image' => $project->preview_image,
            'output_glb_url' => $project->outputGlbUrl(),
            'preview_image_url' => $project->previewImageUrl(),
            'processing_log' => $project->processingLogExcerpt(20),
            'updated_at' => optional($project->updated_at)?->toDateTimeString(),
        ]);
    }

    private function queueEnhancement(GlbTextureEnhancementProject $project): void
    {
        $project->forceFill([
            'status' => 'processing',
            'pipeline_stage' => 'uploaded',
            'progress' => 0,
            'output_glb_path' => null,
            'preview_image' => null,
            'error_message' => null,
            'published_at' => null,
        ])->save();

        $project->appendProcessingLog('Enhancement job dispatched to queue.');

        ProcessGlbTextureEnhancementProject::dispatch($project->id);
    }

    private function authorizeProject(GlbTextureEnhancementProject $project): GlbTextureEnhancementProject
    {
        abort_if($project->user_id !== Auth::id(), 403);

        return $project;
    }

    private function enhancementOptions(array $validated): array
    {
        $mode = $validated['mode'];

        return [
            'mode' => $mode,
            'text_style_prompt' => $validated['text_style_prompt'] ?? null,
            'upscale_factor' => max(2, (int) config('glb_texture_enhancement.default_upscale_factor', 2)),
            'sharpen_amount' => max(1.45, (float) config('glb_texture_enhancement.default_sharpen_amount', 1.45)),
            'contrast_factor' => max(1.18, (float) config('glb_texture_enhancement.default_contrast_factor', 1.18)),
            'color_factor' => max(1.22, (float) config('glb_texture_enhancement.default_color_factor', 1.22)),
            'brightness_factor' => max(1.04, (float) config('glb_texture_enhancement.default_brightness_factor', 1.04)),
            'autocontrast' => $mode === self::MODE_LOCAL_TEXTURE,
        ];
    }

    private function storeUploadedModel(UploadedFile $file, GlbTextureEnhancementProject $project): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'glb') {
            $this->assertValidGlbSignature($file);

            return $this->storeSingleUpload($file, $project, 'original.glb');
        }

        if ($extension === 'gltf') {
            return $this->storeSingleUpload($file, $project, 'original.gltf');
        }

        if ($extension === 'zip') {
            return $this->storeZipGltfUpload($file, $project);
        }

        throw ValidationException::withMessages([
            'model_file' => 'Only .glb, .gltf, or .zip files containing a .gltf package are accepted.',
        ]);
    }

    private function storeSingleUpload(UploadedFile $file, GlbTextureEnhancementProject $project, string $filename): string
    {
        $relativePath = sprintf('enhancement_inputs/%s/%s', $project->uuid, $filename);

        Storage::disk('public')->putFileAs(
            dirname($relativePath),
            $file,
            basename($relativePath)
        );

        return $relativePath;
    }

    private function storeZipGltfUpload(UploadedFile $file, GlbTextureEnhancementProject $project): string
    {
        $extractRelativeDirectory = sprintf('enhancement_inputs/%s/gltf_package', $project->uuid);
        $extractPath = Storage::disk('public')->path($extractRelativeDirectory);

        if (File::isDirectory($extractPath)) {
            File::deleteDirectory($extractPath);
        }

        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive;

        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'model_file' => 'The uploaded ZIP could not be opened.',
            ]);
        }

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = (string) $zip->getNameIndex($index);

                if (! $this->isSafeZipEntry($entryName)) {
                    throw ValidationException::withMessages([
                        'model_file' => 'The ZIP contains an unsafe file path.',
                    ]);
                }

                if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                    continue;
                }

                $targetPath = $extractPath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $entryName);
                File::ensureDirectoryExists(dirname($targetPath));

                $source = $zip->getStream($entryName);
                $target = fopen($targetPath, 'wb');

                if (! $source || ! $target) {
                    throw ValidationException::withMessages([
                        'model_file' => 'A file inside the ZIP could not be extracted.',
                    ]);
                }

                stream_copy_to_stream($source, $target);
                fclose($source);
                fclose($target);
            }
        } finally {
            $zip->close();
        }

        $gltfPath = $this->findFirstGltfInDirectory($extractPath);

        if (! $gltfPath) {
            throw ValidationException::withMessages([
                'model_file' => 'GLTF file not found inside ZIP.',
            ]);
        }

        $publicRoot = Storage::disk('public')->path('');

        return str_replace('\\', '/', Str::after($gltfPath, $publicRoot));
    }

    private function isSafeZipEntry(string $entryName): bool
    {
        $normalized = str_replace('\\', '/', $entryName);
        $parts = explode('/', $normalized);

        return $normalized !== ''
            && ! str_starts_with($normalized, '/')
            && ! preg_match('/^[A-Za-z]:/', $normalized)
            && ! in_array('..', $parts, true);
    }

    private function findFirstGltfInDirectory(string $directory): ?string
    {
        foreach (File::allFiles($directory) as $file) {
            if (strtolower($file->getExtension()) === 'gltf') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function chargeMeshyCredits($user): void
    {
        $amount = self::MESHY_COST;

        DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'credits' => \App\Http\Controllers\System\CreditController::REGISTER_BONUS,
                ]);
            }

            if ($wallet->credits < $amount) {
                throw new RuntimeException(
                    "Credits tidak cukup. Meshy Repair + Retexture membutuhkan {$amount} credits.",
                    402
                );
            }

            $wallet->decrement('credits', $amount);
        });
    }

    private function assertValidGlbSignature(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $signature = $handle ? fread($handle, 4) : false;

        if (is_resource($handle)) {
            fclose($handle);
        }

        if ($signature !== 'glTF') {
            throw ValidationException::withMessages([
                'model_file' => 'Uploaded file is not a valid GLB container.',
            ]);
        }
    }
}
