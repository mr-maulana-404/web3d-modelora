<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;

class RepairMeshStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): string
    {
        $logPath = $workspace->logPath('repair_mesh.log');

        $script = <<<PY
import bpy
import json
import os
import traceback

INPUT_MODEL = {$this->pythonLiteral($workspace->runtimeInputModelPath())}
OUTPUT_GLB = {$this->pythonLiteral($workspace->repairedInputGlbPath())}
MERGE_DISTANCE = 0.0005
MAX_HOLE_SIDES = 32


def set_active_mesh(obj):
    bpy.ops.object.mode_set(mode="OBJECT") if bpy.ops.object.mode_set.poll() else None
    bpy.ops.object.select_all(action="DESELECT")
    obj.select_set(True)
    bpy.context.view_layer.objects.active = obj


def repair_mesh_object(obj):
    set_active_mesh(obj)

    mesh = obj.data
    before_vertices = len(mesh.vertices)
    before_polygons = len(mesh.polygons)

    bpy.ops.object.mode_set(mode="EDIT")
    bpy.ops.mesh.select_all(action="SELECT")

    try:
        bpy.ops.mesh.delete_loose()
    except Exception:
        pass

    bpy.ops.mesh.select_all(action="SELECT")
    try:
        bpy.ops.mesh.remove_doubles(threshold=MERGE_DISTANCE)
    except Exception:
        try:
            bpy.ops.mesh.merge_by_distance(distance=MERGE_DISTANCE)
        except Exception:
            pass

    bpy.ops.mesh.select_all(action="SELECT")
    try:
        bpy.ops.mesh.normals_make_consistent(inside=False)
    except Exception:
        pass

    bpy.ops.mesh.select_all(action="SELECT")
    try:
        bpy.ops.mesh.fill_holes(sides=MAX_HOLE_SIDES)
    except Exception:
        pass

    bpy.ops.object.mode_set(mode="OBJECT")

    try:
        mesh.validate(clean_customdata=False)
        mesh.update()
    except Exception:
        pass

    return {
        "mesh_name": obj.name,
        "vertices_before": before_vertices,
        "vertices_after": len(mesh.vertices),
        "polygons_before": before_polygons,
        "polygons_after": len(mesh.polygons),
    }


try:
    if not os.path.exists(INPUT_MODEL):
        raise RuntimeError(f"Input model is missing: {INPUT_MODEL}")

    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=INPUT_MODEL)

    mesh_objects = [obj for obj in bpy.context.scene.objects if obj.type == "MESH"]

    if not mesh_objects:
        raise RuntimeError("Mesh repair could not find any mesh in the imported model.")

    repaired = []
    for obj in mesh_objects:
        repaired.append(repair_mesh_object(obj))

    os.makedirs(os.path.dirname(OUTPUT_GLB), exist_ok=True)
    bpy.ops.export_scene.gltf(
        filepath=OUTPUT_GLB,
        export_format="GLB",
        check_existing=False,
        export_image_format="AUTO",
    )

    if not os.path.exists(OUTPUT_GLB) or os.path.getsize(OUTPUT_GLB) == 0:
        raise RuntimeError("Mesh repair did not create a valid repaired GLB.")

    print(json.dumps({"output_glb": OUTPUT_GLB, "mesh_count": len(repaired), "meshes": repaired}))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'repair_mesh.py', $script);

        $this->blenderRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        if (! is_file($workspace->repairedInputGlbPath()) || filesize($workspace->repairedInputGlbPath()) === 0) {
            throw new \RuntimeException('Mesh repair finished without a valid repaired GLB file.');
        }

        return $workspace->repairedInputGlbPath();
    }
}
