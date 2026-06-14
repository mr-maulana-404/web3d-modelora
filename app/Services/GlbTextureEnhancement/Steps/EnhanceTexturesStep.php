<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;
use App\Services\GlbTextureEnhancement\PythonRunner;

class EnhanceTexturesStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly PythonRunner $pythonRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): array
    {
        $logPath = $workspace->logPath('enhance_textures.log');

        $this->pythonRunner->assertAvailable($workspace->rootPath(), $logPath);
        $this->pythonRunner->assertPillowAvailable($workspace->rootPath(), $logPath);

        $options = $project->enhancement_options ?: [
            'upscale_factor' => (int) config('glb_texture_enhancement.default_upscale_factor', 2),
            'sharpen_amount' => (float) config('glb_texture_enhancement.default_sharpen_amount', 1.15),
            'contrast_factor' => (float) config('glb_texture_enhancement.default_contrast_factor', 1.10),
            'color_factor' => (float) config('glb_texture_enhancement.default_color_factor', 1.12),
            'brightness_factor' => (float) config('glb_texture_enhancement.default_brightness_factor', 1.03),
            'autocontrast' => false,
        ];

        $optionsJson = json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $script = <<<PY
import json
import os
import re
import shutil
import traceback

from PIL import Image, ImageEnhance, ImageOps, ImageFilter

INPUT_MANIFEST = {$this->pythonLiteral($workspace->originalTextureManifestPath())}
OUTPUT_MANIFEST = {$this->pythonLiteral($workspace->enhancedTextureManifestPath())}
OUTPUT_DIR = {$this->pythonLiteral($workspace->enhancedTexturesDirectory())}
OPTIONS = json.loads({$this->pythonLiteral($optionsJson)})


def slugify(value):
    return re.sub(r"[^A-Za-z0-9._-]+", "_", value).strip("_") or "texture"


def determine_output_path(entry):
    extracted_path = entry.get("extracted_path", "")
    stem, ext = os.path.splitext(os.path.basename(extracted_path))
    ext = ext.lower() or ".png"
    if ext not in [".png", ".jpg", ".jpeg", ".bmp", ".tif", ".tiff", ".webp"]:
        ext = ".png"
    return os.path.join(OUTPUT_DIR, f"{slugify(stem)}_enhanced{ext}")


def save_image(image, output_path):
    ext = os.path.splitext(output_path)[1].lower()
    save_kwargs = {}
    if ext in [".jpg", ".jpeg"] and image.mode == "RGBA":
        image = image.convert("RGB")
    if ext == ".png":
        save_kwargs["compress_level"] = 6
    image.save(output_path, **save_kwargs)


def enhance_base_color(source_path, output_path):
    with Image.open(source_path) as source:
        source.load()
        upscale = max(1, int(OPTIONS.get("upscale_factor", 2)))
        sharpen_amount = float(OPTIONS.get("sharpen_amount", 1.15))
        contrast_factor = float(OPTIONS.get("contrast_factor", 1.10))
        color_factor = float(OPTIONS.get("color_factor", 1.12))
        brightness_factor = float(OPTIONS.get("brightness_factor", 1.03))
        autocontrast = bool(OPTIONS.get("autocontrast", False))

        alpha_channel = None
        if "A" in source.getbands():
            source_rgba = source.convert("RGBA")
            alpha_channel = source_rgba.getchannel("A")
            color_image = source_rgba.convert("RGB")
        else:
            color_image = source.convert("RGB")

        if upscale > 1:
            color_image = color_image.resize(
                (color_image.width * upscale, color_image.height * upscale),
                Image.Resampling.LANCZOS,
            )

        color_image = ImageEnhance.Brightness(color_image).enhance(brightness_factor)

        if autocontrast:
            color_image = ImageOps.autocontrast(color_image, cutoff=1)

        color_image = ImageEnhance.Color(color_image).enhance(color_factor)

        color_image = ImageEnhance.Contrast(color_image).enhance(contrast_factor)

        unsharp_percent = max(80, int(sharpen_amount * 120))
        color_image = color_image.filter(
            ImageFilter.UnsharpMask(radius=1.1, percent=unsharp_percent, threshold=2)
        )

        color_image = ImageEnhance.Sharpness(color_image).enhance(max(1.0, sharpen_amount * 0.85))

        if alpha_channel is not None:
            if upscale > 1:
                alpha_channel = alpha_channel.resize(
                    (color_image.width, color_image.height),
                    Image.Resampling.LANCZOS,
                )
            result = color_image.convert("RGBA")
            result.putalpha(alpha_channel)
        else:
            result = color_image

        save_image(result, output_path)
        return {"width": result.width, "height": result.height, "action": "enhanced"}


def process_entry(entry):
    source_path = entry.get("extracted_path")
    if not source_path or not os.path.exists(source_path):
        raise RuntimeError(f"Extracted texture is missing: {source_path}")

    output_path = determine_output_path(entry)
    role = (entry.get("texture_role") or "baseColor").strip()

    if role in ["normal", "metallicRoughness"]:
        shutil.copyfile(source_path, output_path)
        with Image.open(source_path) as copied:
            copied.load()
            return output_path, {"width": copied.width, "height": copied.height, "action": "copied"}

    if role == "emissive":
        shutil.copyfile(source_path, output_path)
        with Image.open(source_path) as copied:
            copied.load()
            return output_path, {"width": copied.width, "height": copied.height, "action": "copied"}

    info = enhance_base_color(source_path, output_path)
    return output_path, info


try:
    with open(INPUT_MANIFEST, "r", encoding="utf-8") as handle:
        manifest = json.load(handle)

    os.makedirs(OUTPUT_DIR, exist_ok=True)

    enhanced_manifest = {
        "textures": [],
        "warnings": [],
        "options": OPTIONS,
    }

    for texture in manifest.get("textures", []):
        try:
            output_path, info = process_entry(texture)
            enhanced_entry = dict(texture)
            enhanced_entry["enhanced_path"] = output_path
            enhanced_entry["enhancement_action"] = info["action"]
            enhanced_entry["enhanced_width"] = info["width"]
            enhanced_entry["enhanced_height"] = info["height"]
            enhanced_manifest["textures"].append(enhanced_entry)
        except Exception as entry_error:
            enhanced_manifest["warnings"].append(
                f"Failed to enhance texture '{texture.get('image_name', 'unknown')}': {entry_error}"
            )

    enhanced_manifest["texture_count"] = len(enhanced_manifest["textures"])

    with open(OUTPUT_MANIFEST, "w", encoding="utf-8") as handle:
        json.dump(enhanced_manifest, handle, indent=2)

    print(json.dumps({"texture_count": enhanced_manifest["texture_count"], "warnings": enhanced_manifest["warnings"]}))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'enhance_textures.py', $script);

        $this->pythonRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        $manifest = $this->readJsonFile(
            $workspace->enhancedTextureManifestPath(),
            'Texture enhancement did not produce a readable enhanced manifest.'
        );

        if (($manifest['texture_count'] ?? 0) < 1 || empty($manifest['textures'])) {
            throw new \RuntimeException('Texture enhancement did not generate any usable enhanced texture.');
        }

        return $manifest;
    }
}
