<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;

class RebindEnhancedTexturesStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): void
    {
        $logPath = $workspace->logPath('rebind.log');

        $script = <<<PY
import bpy
import json
import os
import traceback

INPUT_MODEL = {$this->pythonLiteral($workspace->activeInputModelPath())}
INPUT_MANIFEST = {$this->pythonLiteral($workspace->enhancedTextureManifestPath())}
OUTPUT_BLEND = {$this->pythonLiteral($workspace->reboundBlendPath())}


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


def find_node(material, entry):
    node_tree = material.node_tree
    if node_tree is None:
        return None

    node = node_tree.nodes.get(entry.get("node_name"))
    if node is not None and node.type == "TEX_IMAGE":
        return node

    for candidate in node_tree.nodes:
        if candidate.type != "TEX_IMAGE":
            continue
        if candidate.image and candidate.image.name == entry.get("image_name"):
            return candidate
        if detect_texture_role(candidate) == entry.get("texture_role"):
            return candidate

    return None


try:
    with open(INPUT_MANIFEST, "r", encoding="utf-8") as handle:
        manifest = json.load(handle)

    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=INPUT_MODEL)

    success_count = 0
    warnings = []

    for entry in manifest.get("textures", []):
        material_name = entry.get("material_name")
        material = bpy.data.materials.get(material_name)
        if material is None:
            warnings.append(f"Material '{material_name}' could not be found for rebinding.")
            continue

        if not material.use_nodes or material.node_tree is None:
            warnings.append(f"Material '{material_name}' does not support node-based rebinding.")
            continue

        node = find_node(material, entry)
        if node is None:
            warnings.append(
                f"Texture node '{entry.get('node_name')}' could not be matched in material '{material_name}'."
            )
            continue

        enhanced_path = entry.get("enhanced_path")
        if not enhanced_path or not os.path.exists(enhanced_path):
            warnings.append(f"Enhanced texture file is missing for material '{material_name}'.")
            continue

        image = bpy.data.images.load(enhanced_path, check_existing=False)
        image.filepath = enhanced_path
        image.filepath_raw = enhanced_path

        try:
            if entry.get("texture_role") in ["normal", "metallicRoughness"]:
                image.colorspace_settings.name = "Non-Color"
            else:
                image.colorspace_settings.name = "sRGB"
        except Exception:
            pass

        node.image = image
        try:
            image.pack()
        except Exception as pack_error:
            warnings.append(f"Enhanced texture '{enhanced_path}' could not be packed: {pack_error}")
        success_count += 1

    if success_count < 1:
        raise RuntimeError("No textures were successfully rebound to the imported GLB materials.")

    bpy.ops.wm.save_as_mainfile(filepath=OUTPUT_BLEND)
    print(json.dumps({"success_count": success_count, "warnings": warnings}))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'rebind_enhanced_textures.py', $script);

        $this->blenderRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        if (! is_file($workspace->reboundBlendPath())) {
            throw new \RuntimeException('Rebinding step did not produce a working Blender scene file.');
        }
    }
}
