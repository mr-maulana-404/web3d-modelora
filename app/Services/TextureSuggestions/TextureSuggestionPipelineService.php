<?php

namespace App\Services\TextureSuggestions;

use App\Models\Model3D;
use App\Models\ModelPart;
use App\Models\User;
use App\Models\UserTexture;
use App\Services\TextureSuggestions\Contracts\TextureSuggestionProvider;
use App\Services\TextureSuggestions\Providers\GeminiTextureSuggestionProvider;
use App\Services\TextureSuggestions\Providers\OpenRouterTextureSuggestionProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TextureSuggestionPipelineService
{
    public function __construct(
        protected GeminiTextureSuggestionProvider $geminiProvider,
        protected OpenRouterTextureSuggestionProvider $openRouterProvider
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateForPart(User $user, Model3D $model, ModelPart $part, array $options = []): array
    {
        if (! config('texture_suggestions.enabled', true)) {
            throw new RuntimeException('Texture suggestion sedang dinonaktifkan oleh konfigurasi.');
        }

        $limit = (int) ($options['limit'] ?? config('texture_suggestions.default_limit', 3));
        $limit = max(1, min($limit, (int) config('texture_suggestions.max_generated_textures', 4)));

        $context = [
            'model_name' => $model->name,
            'model_description' => Str::limit((string) $model->description, 300),
            'age_category' => $model->age_category,
            'gender_category' => $model->gender_category,
            'selected_part_name' => $part->part_name,
            'selected_mesh_name' => $part->mesh_name,
            'user_prompt' => trim((string) ($options['prompt'] ?? '')),
            'style' => trim((string) ($options['style'] ?? 'auto')),
            'limit' => $limit,
        ];

        $screenshot = $this->normalizeScreenshot($options['screenshot'] ?? null);
        $providerKey = (string) config('texture_suggestions.provider', 'gemini');
        $provider = $this->resolveProvider($providerKey);
        $providerName = $this->providerName($providerKey);

        if (! $provider) {
            throw new RuntimeException('Provider AI belum dikonfigurasi.');
        }

        $generatedAssets = $provider->generate($context, $limit, $screenshot);

        if ($generatedAssets === []) {
            throw new RuntimeException($providerName.' tidak mengembalikan texture baru.');
        }

        $suggestions = collect($generatedAssets)
            ->map(fn (array $asset, int $index) => $this->persistGeneratedTexture(
                $user,
                $part,
                $asset,
                $index + 1
            ))
            ->values();

        return [
            'provider' => [
                'mode' => $providerKey.'_generated',
                'name' => $providerName,
            ],
            'summary' => sprintf(
                '%d texture baru berhasil dibuat oleh %s dan otomatis masuk ke My Textures.',
                $suggestions->count(),
                $providerName
            ),
            'selected_part' => [
                'id' => $part->id,
                'name' => $part->part_name,
                'mesh_name' => $part->mesh_name,
            ],
            'generated_texture_ids' => $suggestions->pluck('texture_id')->all(),
            'suggestions' => $suggestions->all(),
        ];
    }

    protected function resolveProvider(string $provider): ?TextureSuggestionProvider
    {
        return match ($provider) {
            'gemini' => $this->geminiProvider,
            'openrouter' => $this->openRouterProvider,
            default => null,
        };
    }

    protected function providerName(string $provider): string
    {
        return match ($provider) {
            'openrouter' => 'OpenRouter',
            default => 'Gemini',
        };
    }

    /**
     * @param  array<string, mixed>  $asset
     * @return array<string, mixed>
     */
    protected function persistGeneratedTexture(User $user, ModelPart $part, array $asset, int $index): array
    {
        $binary = $asset['binary'] ?? null;

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('Texture hasil AI kosong.');
        }

        $mimeType = (string) ($asset['mime_type'] ?? 'image/png');
        $extension = $this->extensionFromMimeType($mimeType);
        $safeBaseName = Str::slug((string) ($asset['name'] ?? "AI {$part->part_name} Texture {$index}"));
        $filename = $safeBaseName.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$extension;
        $path = sprintf(
            'user_textures/user_%d/%s/%s',
            $user->id,
            config('texture_suggestions.storage_directory', 'ai_generated'),
            $filename
        );

        Storage::disk('public')->put($path, $binary);

        $texture = UserTexture::create([
            'user_id' => $user->id,
            'name' => (string) ($asset['name'] ?? "AI {$part->part_name} Texture {$index}"),
            'texture_path' => $path,
        ]);

        return [
            'texture_id' => $texture->id,
            'texture_type' => 'user',
            'name' => $texture->name,
            'category' => 'AI Generated',
            'library' => 'My Textures',
            'texture_path' => $texture->texture_path,
            'preview_url' => Storage::disk('public')->url($texture->texture_path),
            'reason' => (string) ($asset['reason'] ?? 'Generated by AI.'),
            'application_hint' => 'Saved to My Textures and ready to apply.',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function normalizeScreenshot(mixed $raw): ?array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        if (! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $raw, $matches)) {
            return null;
        }

        $decoded = base64_decode($matches[2], true);

        if ($decoded === false) {
            return null;
        }

        if (strlen($decoded) > (int) config('texture_suggestions.max_inline_image_bytes', 2097152)) {
            return null;
        }

        return [
            'mime_type' => $matches[1],
            'base64' => $matches[2],
        ];
    }

    protected function extensionFromMimeType(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
    }
}
