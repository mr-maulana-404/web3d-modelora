<?php

namespace App\Services\GlbTextureEnhancement;

use App\Models\GlbTextureEnhancementProject;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GlbTextureEnhancementWorkspace
{
    public function __construct(
        private readonly GlbTextureEnhancementProject $project,
    ) {
    }

    public function ensureDirectories(): void
    {
        $directories = [
            $this->rootPath(),
            $this->runtimeInputDirectory(),
            $this->extractedTexturesDirectory(),
            $this->enhancedTexturesDirectory(),
            $this->manifestsDirectory(),
            $this->workingBlenderDirectory(),
            $this->runtimeOutputDirectory(),
            $this->logsDirectory(),
            $this->scriptsDirectory(),
            $this->publicOutputDirectory(),
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists($directory);
        }
    }

    public function ensureWritable(): void
    {
        foreach ([$this->rootPath(), $this->publicOutputDirectory()] as $directory) {
            if (! is_writable($directory)) {
                throw new \RuntimeException(sprintf('Working directory is not writable: %s', $directory));
            }
        }
    }

    public function syncInputModelFromPublicDisk(): void
    {
        if (! $this->project->input_glb_path || ! Storage::disk('public')->exists($this->project->input_glb_path)) {
            throw new \RuntimeException('Input model file is missing from storage.');
        }

        $sourcePath = Storage::disk('public')->path($this->project->input_glb_path);

        if ($this->inputIsGltf()) {
            if ($this->inputIsPackagedGltf()) {
                $sourceDirectory = Storage::disk('public')->path($this->publicInputGltfPackageDirectory());
                $targetDirectory = $this->runtimeInputGltfPackageDirectory();

                if (File::isDirectory($targetDirectory)) {
                    File::deleteDirectory($targetDirectory);
                }

                File::copyDirectory($sourceDirectory, $targetDirectory);

                return;
            }

            File::copy($sourcePath, $this->runtimeInputGltfPath());

            return;
        }

        File::copy($sourcePath, $this->runtimeInputGlbPath());
    }

    public function project(): GlbTextureEnhancementProject
    {
        return $this->project;
    }

    public function uuid(): string
    {
        return $this->project->uuid;
    }

    public function rootPath(): string
    {
        return rtrim((string) config('glb_texture_enhancement.working_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$this->uuid();
    }

    public function runtimeInputDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'input';
    }

    public function runtimeInputGlbPath(): string
    {
        return $this->runtimeInputDirectory().DIRECTORY_SEPARATOR.'original.glb';
    }

    public function runtimeInputGltfPackageDirectory(): string
    {
        return $this->runtimeInputDirectory().DIRECTORY_SEPARATOR.'gltf_package';
    }

    public function runtimeInputGltfPath(): string
    {
        if (! $this->inputIsPackagedGltf()) {
            return $this->runtimeInputDirectory().DIRECTORY_SEPARATOR.'original.gltf';
        }

        return $this->runtimeInputGltfPackageDirectory()
            .DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $this->inputGltfPackageRelativePath());
    }

    public function runtimeInputModelPath(): string
    {
        return $this->inputIsGltf()
            ? $this->runtimeInputGltfPath()
            : $this->runtimeInputGlbPath();
    }

    public function repairedInputGlbPath(): string
    {
        return $this->runtimeInputDirectory().DIRECTORY_SEPARATOR.'repaired_input.glb';
    }

    public function meshyInputGlbPath(): string
    {
        return $this->runtimeInputDirectory().DIRECTORY_SEPARATOR.'meshy_input.glb';
    }

    public function activeInputModelPath(): string
    {
        return File::exists($this->repairedInputGlbPath())
            ? $this->repairedInputGlbPath()
            : $this->runtimeInputModelPath();
    }

    private function inputIsGltf(): bool
    {
        return strtolower(pathinfo((string) $this->project->input_glb_path, PATHINFO_EXTENSION)) === 'gltf';
    }

    private function inputIsPackagedGltf(): bool
    {
        return str_contains(str_replace('\\', '/', (string) $this->project->input_glb_path), '/gltf_package/');
    }

    private function publicInputGltfPackageDirectory(): string
    {
        $inputPath = str_replace('\\', '/', (string) $this->project->input_glb_path);
        $marker = '/gltf_package/';
        $markerPosition = strpos($inputPath, $marker);

        if ($markerPosition === false) {
            throw new \RuntimeException('Input GLTF package path is malformed.');
        }

        return substr($inputPath, 0, $markerPosition).'/gltf_package';
    }

    private function inputGltfPackageRelativePath(): string
    {
        $inputPath = str_replace('\\', '/', (string) $this->project->input_glb_path);
        $marker = '/gltf_package/';
        $markerPosition = strpos($inputPath, $marker);

        if ($markerPosition === false) {
            throw new \RuntimeException('Input GLTF package path is malformed.');
        }

        return substr($inputPath, $markerPosition + strlen($marker));
    }

    public function extractedTexturesDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'extracted_textures';
    }

    public function enhancedTexturesDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'enhanced_textures';
    }

    public function manifestsDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'manifests';
    }

    public function analysisManifestPath(): string
    {
        return $this->manifestsDirectory().DIRECTORY_SEPARATOR.'analysis.json';
    }

    public function originalTextureManifestPath(): string
    {
        return $this->manifestsDirectory().DIRECTORY_SEPARATOR.'original_texture_manifest.json';
    }

    public function enhancedTextureManifestPath(): string
    {
        return $this->manifestsDirectory().DIRECTORY_SEPARATOR.'enhanced_texture_manifest.json';
    }

    public function workingBlenderDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'working_blender';
    }

    public function reboundBlendPath(): string
    {
        return $this->workingBlenderDirectory().DIRECTORY_SEPARATOR.'rebound_scene.blend';
    }

    public function runtimeOutputDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'output';
    }

    public function logsDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'logs';
    }

    public function scriptsDirectory(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.'scripts';
    }

    public function scriptPath(string $filename): string
    {
        return $this->scriptsDirectory().DIRECTORY_SEPARATOR.$filename;
    }

    public function logPath(string $filename): string
    {
        return $this->logsDirectory().DIRECTORY_SEPARATOR.$filename;
    }

    public function publicOutputDirectory(): string
    {
        return rtrim((string) config('glb_texture_enhancement.public_output_root'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$this->uuid();
    }

    public function publicRelativeOutputDirectory(): string
    {
        return 'glb_texture_outputs/'.$this->uuid();
    }

    public function publicEnhancedGlbAbsolutePath(): string
    {
        return $this->publicOutputDirectory().DIRECTORY_SEPARATOR.'enhanced_model.glb';
    }

    public function publicEnhancedGlbRelativePath(): string
    {
        return $this->publicRelativeOutputDirectory().'/enhanced_model.glb';
    }

    public function publicPreviewAbsolutePath(): string
    {
        return $this->publicOutputDirectory().DIRECTORY_SEPARATOR.'preview.png';
    }

    public function publicPreviewRelativePath(): string
    {
        return $this->publicRelativeOutputDirectory().'/preview.png';
    }
}
