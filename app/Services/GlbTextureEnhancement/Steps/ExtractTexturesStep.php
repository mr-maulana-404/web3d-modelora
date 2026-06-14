<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;

class ExtractTexturesStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): array
    {
        $logPath = $workspace->logPath('extract_textures.log');

        $script = <<<PY
import bpy
import json
import os
import re
import traceback

INPUT_MODEL = {$this->pythonLiteral($workspace->activeInputModelPath())}
EXTRACT_DIR = {$this->pythonLiteral($workspace->extractedTexturesDirectory())}
OUTPUT_JSON = {$this->pythonLiteral($workspace->originalTextureManifestPath())}


def slugify(value):
    return re.sub(r"[^A-Za-z0-9._-]+", "_", value).strip("_") or "texture"


def detect_texture_role(node):
    for output in node.outputs:
        for link in output.links:
            target_node = getattr(link, "to_node", None)
            target_socket = getattr(link, "to_socket", None)
            socket_name = getattr(target_socket, "name", "").lower()

            if "base color" in socket_name:
                return "baseColor"
            if "emission" in socket_name:
                return "emissive"
            if "metallic" in socket_name or "roughness" in socket_name:
                return "metallicRoughness"
            if "normal" in socket_name:
                return "normal"
            if target_node and target_node.type == "NORMAL_MAP":
                return "normal"

    label = f"{node.label} {node.name}".lower()
    if "normal" in label:
        return "normal"
    if "rough" in label or "metal" in label:
        return "metallicRoughness"
    if "emiss" in label:
        return "emissive"

    return "baseColor"


def image_extension(image):
    image_path = getattr(image, "filepath", "") or ""
    _, ext = os.path.splitext(image_path)
    if ext:
        return ext.lower()

    mapping = {
        "PNG": ".png",
        "JPEG": ".jpg",
        "JPG": ".jpg",
        "TARGA": ".tga",
        "BMP": ".bmp",
        "TIFF": ".tif",
        "OPEN_EXR": ".exr",
    }
    return mapping.get((image.file_format or "PNG").upper(), ".png")


def blender_image_format(image, ext):
    if ext in [".jpg", ".jpeg"]:
        return "JPEG"
    if ext == ".tga":
        return "TARGA"
    if ext == ".bmp":
        return "BMP"
    if ext in [".tif", ".tiff"]:
        return "TIFF"
    if ext == ".exr":
        return "OPEN_EXR"
    return "PNG"


def save_image(image, output_path):
    previous_path = image.filepath_raw
    previous_format = image.file_format
    ext = image_extension(image)
    target_format = blender_image_format(image, ext)

    try:
        image.filepath_raw = output_path
        image.file_format = target_format
        image.save()
    finally:
        image.filepath_raw = previous_path
        image.file_format = previous_format


try:
    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=INPUT_MODEL)
    os.makedirs(EXTRACT_DIR, exist_ok=True)

    manifest = {
        "textures": [],
        "warnings": [],
    }

    saved_images = {}
    materials = {}

    for obj in [item for item in bpy.context.scene.objects if item.type == "MESH"]:
        for slot in obj.material_slots:
            if slot.material is not None:
                materials[slot.material.name] = slot.material

    for material in materials.values():
        if not material.use_nodes or material.node_tree is None:
            manifest["warnings"].append(f"Material '{material.name}' does not have a node tree.")
            continue

        for node in material.node_tree.nodes:
            if node.type != "TEX_IMAGE" or node.image is None:
                continue

            image = node.image
            role = detect_texture_role(node)
            image_key = image.name

            if image_key not in saved_images:
                ext = image_extension(image)
                filename = f"{slugify(image.name)}{ext}"
                output_path = os.path.join(EXTRACT_DIR, filename)

                try:
                    save_image(image, output_path)
                    size = list(image.size) if getattr(image, "size", None) else [0, 0]
                    saved_images[image_key] = {
                        "path": output_path,
                        "width": int(size[0]) if len(size) > 0 else 0,
                        "height": int(size[1]) if len(size) > 1 else 0,
                    }
                except Exception as save_error:
                    manifest["warnings"].append(
                        f"Failed to extract texture '{image.name}' from material '{material.name}': {save_error}"
                    )
                    continue

            saved = saved_images[image_key]
            manifest["textures"].append({
                "material_name": material.name,
                "node_name": node.name,
                "image_name": image.name,
                "texture_role": role,
                "extracted_path": saved["path"],
                "width": saved["width"],
                "height": saved["height"],
            })

    manifest["texture_count"] = len(manifest["textures"])

    with open(OUTPUT_JSON, "w", encoding="utf-8") as handle:
        json.dump(manifest, handle, indent=2)

    print(json.dumps({"texture_count": manifest["texture_count"], "warnings": manifest["warnings"]}))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'extract_textures.py', $script);

        $this->blenderRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        $manifest = $this->readJsonFile(
            $workspace->originalTextureManifestPath(),
            'Texture extraction did not produce a readable manifest.'
        );

        if (($manifest['texture_count'] ?? 0) < 1 || empty($manifest['textures'])) {
            throw new \RuntimeException('Texture extraction finished without any usable texture output.');
        }

        return $manifest;
    }
}
