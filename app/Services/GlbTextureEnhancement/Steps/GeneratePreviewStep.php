<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;

class GeneratePreviewStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): ?string
    {
        $logPath = $workspace->logPath('preview.log');

        $script = <<<PY
import bpy
import json
import math
import mathutils
import os
import traceback

INPUT_GLB = {$this->pythonLiteral($workspace->publicEnhancedGlbAbsolutePath())}
OUTPUT_PNG = {$this->pythonLiteral($workspace->publicPreviewAbsolutePath())}


def scene_bbox(mesh_objects):
    min_corner = mathutils.Vector((float("inf"), float("inf"), float("inf")))
    max_corner = mathutils.Vector((float("-inf"), float("-inf"), float("-inf")))

    for obj in mesh_objects:
        for corner in obj.bound_box:
            world_corner = obj.matrix_world @ mathutils.Vector(corner)
            min_corner.x = min(min_corner.x, world_corner.x)
            min_corner.y = min(min_corner.y, world_corner.y)
            min_corner.z = min(min_corner.z, world_corner.z)
            max_corner.x = max(max_corner.x, world_corner.x)
            max_corner.y = max(max_corner.y, world_corner.y)
            max_corner.z = max(max_corner.z, world_corner.z)

    return min_corner, max_corner


def configure_camera(scene, center, radius):
    camera_data = bpy.data.cameras.new("PreviewCamera")
    camera = bpy.data.objects.new("PreviewCamera", camera_data)
    scene.collection.objects.link(camera)
    scene.camera = camera

    camera.location = (
        center.x + radius * 1.8,
        center.y - radius * 1.8,
        center.z + radius * 1.1,
    )

    direction = center - camera.location
    camera.rotation_euler = direction.to_track_quat("-Z", "Y").to_euler()

    return camera


try:
    if not os.path.exists(INPUT_GLB):
        raise RuntimeError(f"Enhanced GLB is missing: {INPUT_GLB}")

    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=INPUT_GLB)

    scene = bpy.context.scene
    mesh_objects = [obj for obj in scene.objects if obj.type == "MESH"]

    if not mesh_objects:
        raise RuntimeError("Preview render could not find any mesh in the exported GLB.")

    min_corner, max_corner = scene_bbox(mesh_objects)
    center = (min_corner + max_corner) / 2.0
    dimensions = max_corner - min_corner
    radius = max(dimensions.x, dimensions.y, dimensions.z) or 1.0

    world = bpy.data.worlds.new("PreviewWorld")
    scene.world = world
    world.use_nodes = True
    background = world.node_tree.nodes.get("Background")
    background.inputs[0].default_value = (0.94, 0.95, 0.97, 1.0)
    background.inputs[1].default_value = 1.0

    configure_camera(scene, center, radius)

    sun_data = bpy.data.lights.new(name="PreviewSun", type="SUN")
    sun = bpy.data.objects.new(name="PreviewSun", object_data=sun_data)
    scene.collection.objects.link(sun)
    sun.location = (center.x + radius, center.y + radius, center.z + radius * 2.0)
    sun.rotation_euler = (math.radians(40), 0.0, math.radians(35))
    sun_data.energy = 2.5

    engine_options = [item.identifier for item in bpy.types.RenderSettings.bl_rna.properties["engine"].enum_items]
    if "BLENDER_EEVEE_NEXT" in engine_options:
        scene.render.engine = "BLENDER_EEVEE_NEXT"
    elif "BLENDER_EEVEE" in engine_options:
        scene.render.engine = "BLENDER_EEVEE"
    else:
        scene.render.engine = engine_options[0]

    scene.render.resolution_x = 1024
    scene.render.resolution_y = 1024
    scene.render.film_transparent = False
    scene.render.image_settings.file_format = "PNG"
    scene.render.filepath = OUTPUT_PNG

    os.makedirs(os.path.dirname(OUTPUT_PNG), exist_ok=True)
    bpy.ops.render.render(write_still=True)

    if not os.path.exists(OUTPUT_PNG) or os.path.getsize(OUTPUT_PNG) == 0:
        raise RuntimeError("Preview render did not generate a valid PNG file.")

    print(json.dumps({"preview": OUTPUT_PNG}))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'generate_preview.py', $script);

        $this->blenderRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        if (! is_file($workspace->publicPreviewAbsolutePath()) || filesize($workspace->publicPreviewAbsolutePath()) === 0) {
            throw new \RuntimeException('Preview generation finished without a valid PNG file.');
        }

        $relativePreviewPath = $workspace->publicPreviewRelativePath();

        $project->forceFill([
            'preview_image' => $relativePreviewPath,
        ])->save();

        return $relativePreviewPath;
    }
}
