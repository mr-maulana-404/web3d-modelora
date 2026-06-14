<?php

namespace App\Services\GlbTextureEnhancement\Steps;

use App\Models\GlbTextureEnhancementProject;
use App\Services\GlbTextureEnhancement\BlenderRunner;
use App\Services\GlbTextureEnhancement\GlbTextureEnhancementWorkspace;

class ExportEnhancedGlbStep extends AbstractGlbTextureEnhancementStep
{
    public function __construct(
        private readonly BlenderRunner $blenderRunner,
    ) {
    }

    public function handle(GlbTextureEnhancementProject $project, GlbTextureEnhancementWorkspace $workspace): string
    {
        $logPath = $workspace->logPath('export_glb.log');

        $script = <<<PY
import bpy
import json
import os
import traceback

INPUT_BLEND = {$this->pythonLiteral($workspace->reboundBlendPath())}
OUTPUT_GLB = {$this->pythonLiteral($workspace->publicEnhancedGlbAbsolutePath())}

try:
    if not os.path.exists(INPUT_BLEND):
        raise RuntimeError(f"Working Blender scene is missing: {INPUT_BLEND}")

    os.makedirs(os.path.dirname(OUTPUT_GLB), exist_ok=True)

    bpy.ops.wm.open_mainfile(filepath=INPUT_BLEND)
    bpy.ops.export_scene.gltf(
        filepath=OUTPUT_GLB,
        export_format="GLB",
        check_existing=False,
        export_image_format="AUTO",
        export_keep_originals=False,
    )

    if not os.path.exists(OUTPUT_GLB) or os.path.getsize(OUTPUT_GLB) == 0:
        raise RuntimeError("Enhanced GLB export did not create a valid output file.")

    print(json.dumps({"output_glb": OUTPUT_GLB, "size": os.path.getsize(OUTPUT_GLB)}))
except Exception:
    traceback.print_exc()
    raise
PY;

        $scriptPath = $this->writeScript($workspace, 'export_enhanced_glb.py', $script);

        $this->blenderRunner->runScript($scriptPath, $workspace->rootPath(), $logPath);

        if (! is_file($workspace->publicEnhancedGlbAbsolutePath()) || filesize($workspace->publicEnhancedGlbAbsolutePath()) === 0) {
            throw new \RuntimeException('Enhanced GLB export finished without a valid GLB file.');
        }

        $relativeOutputPath = $workspace->publicEnhancedGlbRelativePath();

        $project->forceFill([
            'output_glb_path' => $relativeOutputPath,
        ])->save();

        return $relativeOutputPath;
    }
}
