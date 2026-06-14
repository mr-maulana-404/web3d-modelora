<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;

class AnalyzeGlbStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): array
    {
        $logPath = $workspace->logPath('analyze.log');

        $this->blenderRunner->assertAvailable($workspace->rootPath(), $logPath);

        $script = <<<PY
import bpy
import json
import os
import traceback

INPUT_MODEL = {$this->pythonLiteral($workspace->activeInputModelPath())}
OUTPUT_JSON = {$this->pythonLiteral($workspace->analysisManifestPath())}


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


def collect_materials(mesh_objects):
    materials = {}
    for obj in mesh_objects:
        for slot in obj.material_slots:
            if slot.material is not None:
                materials[slot.material.name] = slot.material
    return list(materials.values())


try:
    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=INPUT_MODEL)

    mesh_objects = [obj for obj in bpy.context.scene.objects if obj.type == "MESH"]
    materials = collect_materials(mesh_objects)
    uv_present = any(len(obj.data.uv_layers) > 0 for obj in mesh_objects if getattr(obj, "data", None))
    mesh_names = [obj.name for obj in mesh_objects]
    material_names = [material.name for material in materials]

    texture_images = []
    warnings = []

    for material in materials:
        if not material.use_nodes or material.node_tree is None:
            warnings.append(f"Material '{material.name}' does not have a node tree.")
            continue

        for node in material.node_tree.nodes:
            if node.type != "TEX_IMAGE" or node.image is None:
                continue

            size = list(node.image.size) if getattr(node.image, "size", None) else [0, 0]
            texture_images.append({
                "material_name": material.name,
                "node_name": node.name,
                "image_name": node.image.name,
                "texture_role": detect_texture_role(node),
                "width": int(size[0]) if len(size) > 0 else 0,
                "height": int(size[1]) if len(size) > 1 else 0,
            })

    result = {
        "mesh_count": len(mesh_objects),
        "mesh_names": mesh_names,
        "material_count": len(materials),
        "material_names": material_names,
        "texture_count": len(texture_images),
        "texture_images": texture_images,
        "uv_present": uv_present,
        "warnings": warnings,
    }

    with open(OUTPUT_JSON, "w", encoding="utf-8") as handle:
        json.dump(result, handle, indent=2)

    print(json.dumps(result))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'analyze_glb.py', $script);

        $this->blenderRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        $analysis = $this->readJsonFile(
            $workspace->analysisManifestPath(),
            'GLB analysis did not produce a readable analysis manifest.'
        );

        if (($analysis['mesh_count'] ?? 0) < 1) {
            throw new \RuntimeException('GLB file does not contain any mesh data.');
        }

        if (($analysis['material_count'] ?? 0) < 1) {
            throw new \RuntimeException('GLB file does not contain any material that can be enhanced.');
        }

        if (($analysis['texture_count'] ?? 0) < 1) {
            throw new \RuntimeException('GLB file does not contain any image texture that can be extracted.');
        }

        if (! ($analysis['uv_present'] ?? false)) {
            throw new \RuntimeException('GLB file does not contain a usable UV map for texture enhancement.');
        }

        $analysis['texture_role_summary'] = $this->buildTextureRoleSummary($analysis['texture_images'] ?? []);
        $analysis['largest_texture'] = $this->findLargestTexture($analysis['texture_images'] ?? []);

        $project->forceFill([
            'analysis_meta' => $analysis,
        ])->save();

        return $analysis;
    }

    private function buildTextureRoleSummary(array $textures): array
    {
        $summary = [];

        foreach ($textures as $texture) {
            $role = (string) ($texture['texture_role'] ?? 'unknown');
            $summary[$role] = ($summary[$role] ?? 0) + 1;
        }

        ksort($summary);

        return $summary;
    }

    private function findLargestTexture(array $textures): ?array
    {
        $largestTexture = null;
        $largestArea = -1;

        foreach ($textures as $texture) {
            $width = (int) ($texture['width'] ?? 0);
            $height = (int) ($texture['height'] ?? 0);
            $area = $width * $height;

            if ($area > $largestArea) {
                $largestArea = $area;
                $largestTexture = $texture;
            }
        }

        return $largestTexture;
    }
}
