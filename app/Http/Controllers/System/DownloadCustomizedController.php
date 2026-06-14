<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ModelCustomization;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use ZipArchive;

class DownloadCustomizedController extends Controller
{
    public function obj(ModelCustomization $customization, BlenderRunner $blenderRunner): BinaryFileResponse|JsonResponse
    {
        $this->extendExecutionTime();

        try {
            return $this->downloadObj($customization, $blenderRunner);
        } catch (Throwable $exception) {
            report($exception);

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            return response()->json([
                'message' => $this->downloadErrorMessage($exception, $status),
            ], $status);
        }
    }

    private function downloadObj(ModelCustomization $customization, BlenderRunner $blenderRunner): BinaryFileResponse
    {
        abort_if($customization->user_id !== Auth::id(), 403);

        $customization->load(['model3d.parts', 'textures.part']);
        $model = $customization->model3d;

        abort_if(! $model, 404, 'Original model is missing.');

        $inputPath = storage_path('app/public/'.$model->model_path);
        abort_if(! File::exists($inputPath), 404, 'Original model file is missing.');

        $this->cleanupOldWorkspaces();

        $workspace = storage_path('app/customized_downloads/'.(string) Str::uuid());
        $scriptDirectory = $workspace.DIRECTORY_SEPARATOR.'scripts';
        $outputDirectory = $workspace.DIRECTORY_SEPARATOR.'output';
        $logDirectory = $workspace.DIRECTORY_SEPARATOR.'logs';

        File::ensureDirectoryExists($scriptDirectory);
        File::ensureDirectoryExists($outputDirectory);
        File::ensureDirectoryExists($logDirectory);

        $manifestPath = $workspace.DIRECTORY_SEPARATOR.'manifest.json';
        $scriptPath = $scriptDirectory.DIRECTORY_SEPARATOR.'bake_customized_obj.py';
        $logPath = $logDirectory.DIRECTORY_SEPARATOR.'blender_obj_bake.log';

        File::put($manifestPath, json_encode($this->buildManifest($customization, $inputPath, $outputDirectory), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::put($scriptPath, $this->buildBlenderScript($manifestPath));

        $blenderRunner->assertAvailable($workspace, $logPath);
        $blenderRunner->runScript($scriptPath, $workspace, $logPath);

        $zipPath = $workspace.DIRECTORY_SEPARATOR.$this->safeFilename($customization->name).'-obj-baked.zip';
        $this->zipDirectory($outputDirectory, $zipPath);

        return response()
            ->download($zipPath, basename($zipPath), [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }

    private function extendExecutionTime(): void
    {
        $timeout = max((int) config('glb_texture_enhancement.process_timeout', 3600), 300);

        @set_time_limit($timeout + 120);
        @ini_set('max_execution_time', (string) ($timeout + 120));
    }

    private function downloadErrorMessage(Throwable $exception, int $status): string
    {
        if ($status === 403) {
            return 'Anda tidak punya akses untuk mendownload customization ini.';
        }

        if ($status === 404) {
            return $exception->getMessage() ?: 'Model asli untuk customization ini tidak ditemukan.';
        }

        if (str_contains($exception->getMessage(), 'Maximum execution time')) {
            return 'Proses bake OBJ melebihi batas waktu server. Silakan coba lagi, atau turunkan kompleksitas/ukuran model.';
        }

        return $exception->getMessage() ?: 'Server gagal membuat baked OBJ.';
    }

    private function buildManifest(ModelCustomization $customization, string $inputPath, string $outputDirectory): array
    {
        return [
            'input_path' => $inputPath,
            'output_directory' => $outputDirectory,
            'obj_filename' => $this->safeFilename($customization->name ?: $customization->model3d?->name ?: 'customized-model').'.obj',
            'bake_resolution' => 2048,
            'customizations' => $customization->textures
                ->map(function ($texture) {
                    return [
                        'mesh_name' => $texture->part?->mesh_name,
                        'texture_type' => $texture->texture_type,
                        'texture_path' => $texture->texture_path
                            ? storage_path('app/public/'.$texture->texture_path)
                            : null,
                        'color_value' => $texture->color_value,
                    ];
                })
                ->filter(fn (array $texture) => filled($texture['mesh_name']))
                ->values()
                ->all(),
        ];
    }

    private function buildBlenderScript(string $manifestPath): string
    {
        $manifestLiteral = json_encode($manifestPath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<PY
import bpy
import json
import os
import re
import traceback

MANIFEST_PATH = {$manifestLiteral}


def safe_name(value):
    value = re.sub(r"[^A-Za-z0-9._-]+", "_", str(value or "item")).strip("_")
    return value or "item"


def base_object_name(value):
    return re.sub(r"\\.\\d{3}$", "", str(value or "")).strip()


def hex_to_rgba(value):
    value = str(value or "#ffffff").strip().lstrip("#")
    if len(value) == 3:
        value = "".join([char * 2 for char in value])
    if len(value) != 6:
        value = "ffffff"
    return (
        int(value[0:2], 16) / 255.0,
        int(value[2:4], 16) / 255.0,
        int(value[4:6], 16) / 255.0,
        1.0,
    )


def principled_node(material):
    if not material.use_nodes:
        material.use_nodes = True
    nodes = material.node_tree.nodes
    return nodes.get("Principled BSDF") or nodes.get("Principled BSDF.001")


def base_color_input(node):
    return node.inputs.get("Base Color") if node else None


def create_texture_material(name, texture_path=None, color_value=None):
    material = bpy.data.materials.new(name)
    material.use_nodes = True
    nodes = material.node_tree.nodes
    links = material.node_tree.links
    bsdf = principled_node(material)
    color_input = base_color_input(bsdf)

    if texture_path and os.path.exists(texture_path):
        image = bpy.data.images.load(texture_path, check_existing=True)
        try:
            image.colorspace_settings.name = "sRGB"
        except Exception:
            pass

        tex_node = nodes.new("ShaderNodeTexImage")
        tex_node.image = image
        tex_node.extension = "REPEAT"
        tex_node.interpolation = "Linear"
        if color_input:
            links.new(tex_node.outputs.get("Color"), color_input)
    elif color_input:
        color_input.default_value = hex_to_rgba(color_value)

    return material


def ensure_materials(object_item):
    if len(object_item.data.materials) == 0:
        material = bpy.data.materials.new(safe_name(object_item.name) + "_default")
        material.diffuse_color = (1, 1, 1, 1)
        material.use_nodes = True
        object_item.data.materials.append(material)

    for slot in object_item.material_slots:
        material = slot.material
        if material is None:
            continue
        if not material.use_nodes:
            color = material.diffuse_color
            material.use_nodes = True
            bsdf = principled_node(material)
            color_input = base_color_input(bsdf)
            if color_input:
                color_input.default_value = color


def ensure_uv(object_item):
    bpy.ops.object.select_all(action="DESELECT")
    object_item.select_set(True)
    bpy.context.view_layer.objects.active = object_item

    if len(object_item.data.uv_layers) > 0:
        return

    bpy.ops.object.mode_set(mode="EDIT")
    bpy.ops.mesh.select_all(action="SELECT")
    bpy.ops.uv.smart_project(angle_limit=1.15192, island_margin=0.02)
    bpy.ops.object.mode_set(mode="OBJECT")


def prepare_bake_target(material, image):
    material.use_nodes = True
    nodes = material.node_tree.nodes

    for node in nodes:
        node.select = False

    bake_node = nodes.new("ShaderNodeTexImage")
    bake_node.name = "__MODELORA_BAKE_TARGET__"
    bake_node.image = image
    bake_node.select = True
    nodes.active = bake_node


def assign_baked_material(object_item, image, baked_path):
    material = bpy.data.materials.new(safe_name(object_item.name) + "_baked")
    material.use_nodes = True
    nodes = material.node_tree.nodes
    links = material.node_tree.links
    bsdf = principled_node(material)
    tex_node = nodes.new("ShaderNodeTexImage")
    tex_node.image = image
    tex_node.extension = "REPEAT"
    tex_node.interpolation = "Linear"
    tex_node.image.filepath = baked_path

    try:
        tex_node.image.colorspace_settings.name = "sRGB"
    except Exception:
        pass

    color_input = base_color_input(bsdf)
    if color_input:
        links.new(tex_node.outputs.get("Color"), color_input)

    object_item.data.materials.clear()
    object_item.data.materials.append(material)


def bake_object(object_item, texture_directory, resolution, index):
    ensure_materials(object_item)
    ensure_uv(object_item)

    image_name = safe_name(object_item.name) + "_baked"
    image = bpy.data.images.new(image_name, width=resolution, height=resolution, alpha=True)
    image.generated_color = (1, 1, 1, 1)

    for slot in object_item.material_slots:
        if slot.material:
            prepare_bake_target(slot.material, image)

    bpy.ops.object.select_all(action="DESELECT")
    object_item.select_set(True)
    bpy.context.view_layer.objects.active = object_item

    bpy.ops.object.bake(type="DIFFUSE", pass_filter={"COLOR"}, margin=8, use_clear=True)

    baked_filename = f"{index:03d}_{safe_name(object_item.name)}_baked.png"
    baked_path = os.path.join(texture_directory, baked_filename)
    image.filepath_raw = baked_path
    image.file_format = "PNG"
    image.save()

    assign_baked_material(object_item, image, baked_path)


def export_obj(output_path):
    bpy.ops.object.select_all(action="DESELECT")
    for object_item in bpy.context.scene.objects:
        if object_item.type == "MESH":
            object_item.select_set(True)

    mesh_objects = [object_item for object_item in bpy.context.scene.objects if object_item.type == "MESH"]
    if mesh_objects:
        bpy.context.view_layer.objects.active = mesh_objects[0]

    try:
        bpy.ops.wm.obj_export(
            filepath=output_path,
            export_selected_objects=True,
            export_materials=True,
            path_mode="RELATIVE",
        )
        return
    except Exception:
        pass

    bpy.ops.export_scene.obj(
        filepath=output_path,
        use_selection=True,
        use_materials=True,
        path_mode="RELATIVE",
    )


def normalize_mtl_paths(output_directory):
    for filename in os.listdir(output_directory):
        if not filename.lower().endswith(".mtl"):
            continue

        path = os.path.join(output_directory, filename)
        with open(path, "r", encoding="utf-8", errors="ignore") as handle:
            contents = handle.read()

        contents = contents.replace("\\\\", "/")
        contents = contents.replace(output_directory.replace("\\\\", "/") + "/", "")

        with open(path, "w", encoding="utf-8") as handle:
            handle.write(contents)


def main():
    with open(MANIFEST_PATH, "r", encoding="utf-8") as handle:
        manifest = json.load(handle)

    input_path = manifest["input_path"]
    output_directory = manifest["output_directory"]
    texture_directory = os.path.join(output_directory, "textures")
    obj_path = os.path.join(output_directory, manifest.get("obj_filename", "customized-model.obj"))
    resolution = int(manifest.get("bake_resolution", 2048))

    os.makedirs(output_directory, exist_ok=True)
    os.makedirs(texture_directory, exist_ok=True)

    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=input_path)

    customizations = {}
    for item in manifest.get("customizations", []):
        mesh_name = item.get("mesh_name")
        if mesh_name:
            customizations[base_object_name(mesh_name)] = item

    mesh_objects = [object_item for object_item in bpy.context.scene.objects if object_item.type == "MESH"]

    for object_item in mesh_objects:
        customization = customizations.get(base_object_name(object_item.name)) or customizations.get(base_object_name(object_item.data.name))

        if customization:
            material = create_texture_material(
                safe_name(object_item.name) + "_custom",
                customization.get("texture_path"),
                customization.get("color_value"),
            )
            object_item.data.materials.clear()
            object_item.data.materials.append(material)

    bpy.context.scene.render.engine = "CYCLES"
    bpy.context.scene.cycles.samples = 16
    bpy.context.scene.render.bake.use_pass_direct = False
    bpy.context.scene.render.bake.use_pass_indirect = False
    bpy.context.scene.render.bake.use_pass_color = True

    for index, object_item in enumerate(mesh_objects, start=1):
        bake_object(object_item, texture_directory, resolution, index)

    export_obj(obj_path)
    normalize_mtl_paths(output_directory)


try:
    main()
except Exception:
    traceback.print_exc()
    raise
PY;
    }

    private function zipDirectory(string $directory, string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create baked OBJ zip file.');
        }

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $zip->addFile(
                $file->getRealPath(),
                str_replace('\\', '/', $file->getRelativePathname())
            );
        }

        $zip->close();
    }

    private function cleanupOldWorkspaces(): void
    {
        $root = storage_path('app/customized_downloads');

        if (! File::isDirectory($root)) {
            return;
        }

        foreach (File::directories($root) as $directory) {
            if (File::lastModified($directory) < now()->subMinutes(10)->timestamp) {
                File::deleteDirectory($directory);
            }
        }
    }

    private function safeFilename(?string $value): string
    {
        return Str::slug($value ?: 'customized-model') ?: 'customized-model';
    }
}
