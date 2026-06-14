<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GlbTextureEnhancementProject extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'model3d_id',
        'name',
        'description',
        'pipeline_type',
        'status',
        'pipeline_stage',
        'progress',
        'input_glb_path',
        'output_glb_path',
        'preview_image',
        'enhancement_options',
        'analysis_meta',
        'processing_log',
        'error_message',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'enhancement_options' => 'array',
            'analysis_meta' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model3d(): BelongsTo
    {
        return $this->belongsTo(Model3D::class);
    }

    public function appendProcessingLog(string $message): void
    {
        $entry = sprintf('[%s] %s', now()->format('Y-m-d H:i:s'), $message);
        $currentLog = trim((string) $this->processing_log);

        $this->forceFill([
            'processing_log' => $currentLog === '' ? $entry : $currentLog.PHP_EOL.$entry,
        ])->save();
    }

    public function processingLogExcerpt(int $lines = 20): string
    {
        $entries = preg_split('/\r\n|\r|\n/', (string) $this->processing_log) ?: [];
        $entries = array_values(array_filter($entries, static fn ($line) => trim($line) !== ''));

        return implode(PHP_EOL, array_slice($entries, -1 * $lines));
    }

    public function inputGlbUrl(): ?string
    {
        return $this->input_glb_path && Storage::disk('public')->exists($this->input_glb_path)
            ? Storage::disk('public')->url($this->input_glb_path)
            : null;
    }

    public function outputGlbUrl(): ?string
    {
        return $this->output_glb_path && Storage::disk('public')->exists($this->output_glb_path)
            ? Storage::disk('public')->url($this->output_glb_path)
            : null;
    }

    public function previewImageUrl(): ?string
    {
        return $this->preview_image && Storage::disk('public')->exists($this->preview_image)
            ? Storage::disk('public')->url($this->preview_image)
            : null;
    }

    public function statusInsightText(): string
    {
        $sections = [];

        $sections[] = 'Perbaikan texture:';
        foreach ($this->enhancementSummaryLines() as $line) {
            $sections[] = '- '.$line;
        }

        $sections[] = '';
        $sections[] = 'Informasi dari Blender:';
        foreach ($this->blenderInsightLines() as $line) {
            $sections[] = '- '.$line;
        }

        return implode(PHP_EOL, $sections);
    }

    public function enhancementSummaryLines(): array
    {
        $options = $this->enhancement_options ?: [
            'upscale_factor' => (int) config('glb_texture_enhancement.default_upscale_factor', 2),
            'sharpen_amount' => (float) config('glb_texture_enhancement.default_sharpen_amount', 1.0),
            'contrast_factor' => (float) config('glb_texture_enhancement.default_contrast_factor', 1.0),
            'color_factor' => (float) config('glb_texture_enhancement.default_color_factor', 1.0),
            'autocontrast' => false,
        ];

        $roleSummary = $this->textureRoleSummary();
        $lines = [];

        $modeLine = match ($this->status) {
            'ready' => 'Enhancement sudah diterapkan ke GLB final.',
            'failed' => 'Enhancement sempat direncanakan/dijalankan sebelum pipeline berhenti.',
            'processing' => 'Enhancement sedang dijalankan mengikuti konfigurasi pipeline.',
            default => 'Enhancement akan dijalankan saat pipeline dimulai.',
        };

        $lines[] = $modeLine;
        $lines[] = 'Mesh repair lokal dijalankan secara konservatif: remove loose geometry, merge vertex sangat dekat, recalculate normals, dan fill hole kecil.';
        $lines[] = sprintf(
            'Base color/albedo diprioritaskan untuk upscale %sx, sharpen %s, contrast %s, dan color %s.',
            $options['upscale_factor'] ?? 1,
            $options['sharpen_amount'] ?? 1,
            $options['contrast_factor'] ?? 1,
            $options['color_factor'] ?? 1
        );

        if (($options['autocontrast'] ?? false) === true) {
            $lines[] = 'Autocontrast ringan dipakai untuk cleanup warna dasar tanpa mengubah karakter model terlalu agresif.';
        }

        if (($roleSummary['normal'] ?? 0) > 0) {
            $lines[] = 'Normal map terdeteksi dan diperlakukan konservatif agar arah normal tidak rusak oleh sharpen/contrast.';
        }

        if (($roleSummary['metallicRoughness'] ?? 0) > 0) {
            $lines[] = 'Metallic/Roughness map dipertahankan konservatif agar channel material tetap aman.';
        }

        if (($roleSummary['emissive'] ?? 0) > 0) {
            $lines[] = 'Emissive texture dipertahankan lebih aman agar intensitas emissive tidak berubah liar.';
        }

        if ($this->analysis_meta === null) {
            $lines[] = 'Detail per-texture akan muncul lebih informatif setelah analisa Blender selesai.';
        }

        return $lines;
    }

    public function blenderInsightLines(): array
    {
        $analysis = $this->analysis_meta ?: [];

        if ($analysis === []) {
            return ['Belum ada data analisa Blender yang tersimpan untuk project ini.'];
        }

        $roleSummary = $this->textureRoleSummary();
        $largestTexture = $this->largestTextureInfo();
        $warnings = array_values(array_filter($analysis['warnings'] ?? [], static fn ($warning) => trim((string) $warning) !== ''));

        $lines = [];
        $lines[] = sprintf(
            'Mesh %d, material %d, texture %d, UV map %s.',
            (int) ($analysis['mesh_count'] ?? 0),
            (int) ($analysis['material_count'] ?? 0),
            (int) ($analysis['texture_count'] ?? 0),
            ($analysis['uv_present'] ?? false) ? 'tersedia' : 'tidak tersedia'
        );

        if (! empty($analysis['mesh_names']) && is_array($analysis['mesh_names'])) {
            $lines[] = 'Sample mesh: '.$this->implodeSampleList($analysis['mesh_names']).'.';
        }

        if (! empty($analysis['material_names']) && is_array($analysis['material_names'])) {
            $lines[] = 'Sample material: '.$this->implodeSampleList($analysis['material_names']).'.';
        }

        if ($roleSummary !== []) {
            $lines[] = 'Peran texture terdeteksi: '.$this->formatRoleSummary($roleSummary).'.';
        }

        if ($largestTexture !== null) {
            $lines[] = sprintf(
                'Texture terbesar: %s (%dx%d, role %s).',
                $largestTexture['image_name'] ?? 'unknown',
                (int) ($largestTexture['width'] ?? 0),
                (int) ($largestTexture['height'] ?? 0),
                Str::headline((string) ($largestTexture['texture_role'] ?? 'unknown'))
            );
        }

        if ($warnings !== []) {
            $lines[] = 'Warning Blender: '.$this->implodeSampleList($warnings, 2).'.';
        } else {
            $lines[] = 'Tidak ada warning Blender yang tersimpan pada analisa terakhir.';
        }

        return $lines;
    }

    private function textureRoleSummary(): array
    {
        $analysis = $this->analysis_meta ?: [];

        if (isset($analysis['texture_role_summary']) && is_array($analysis['texture_role_summary'])) {
            return $analysis['texture_role_summary'];
        }

        $summary = [];

        foreach ($analysis['texture_images'] ?? [] as $texture) {
            $role = (string) ($texture['texture_role'] ?? 'unknown');
            $summary[$role] = ($summary[$role] ?? 0) + 1;
        }

        ksort($summary);

        return $summary;
    }

    private function largestTextureInfo(): ?array
    {
        $analysis = $this->analysis_meta ?: [];

        if (isset($analysis['largest_texture']) && is_array($analysis['largest_texture'])) {
            return $analysis['largest_texture'];
        }

        $largest = null;
        $largestArea = -1;

        foreach ($analysis['texture_images'] ?? [] as $texture) {
            $width = (int) ($texture['width'] ?? 0);
            $height = (int) ($texture['height'] ?? 0);
            $area = $width * $height;

            if ($area > $largestArea) {
                $largestArea = $area;
                $largest = $texture;
            }
        }

        return $largest;
    }

    private function formatRoleSummary(array $roleSummary): string
    {
        $parts = [];

        foreach ($roleSummary as $role => $count) {
            $parts[] = Str::headline((string) $role).' '.$count;
        }

        return implode(', ', $parts);
    }

    private function implodeSampleList(array $values, int $limit = 3): string
    {
        $values = array_values(array_filter($values, static fn ($value) => trim((string) $value) !== ''));
        $sample = array_slice($values, 0, $limit);
        $suffix = count($values) > $limit ? ' dan lainnya' : '';

        return implode(', ', $sample).$suffix;
    }
}
