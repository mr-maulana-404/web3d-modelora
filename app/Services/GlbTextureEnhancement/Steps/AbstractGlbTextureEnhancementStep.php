<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;
use Illuminate\Support\Facades\File;

abstract class AbstractGlbTextureEnhancementStep
{
    protected function writeScript(GlbTextureEnhancementWorkspace $workspace, string $filename, string $contents): string
    {
        $path = $workspace->scriptPath($filename);

        File::put($path, $contents);

        return $path;
    }

    protected function readJsonFile(string $path, string $errorMessage): array
    {
        if (! File::exists($path)) {
            throw new \RuntimeException($errorMessage);
        }

        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException($errorMessage);
        }

        return $decoded;
    }

    protected function pythonLiteral(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
