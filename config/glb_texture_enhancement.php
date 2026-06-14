<?php

return [
    'enable_pipeline' => env('GLB_TEXTURE_ENABLE_PIPELINE', true),
    'working_root' => env('GLB_TEXTURE_WORKDIR', storage_path('app/glb_texture_runtime')),
    'public_output_root' => env('GLB_TEXTURE_PUBLIC_OUTPUT', storage_path('app/public/glb_texture_outputs')),
    'blender_binary' => env('BLENDER_BINARY', 'blender'),
    'python_binary' => env('PYTHON_BINARY', 'python3'),
    'process_timeout' => (int) env('GLB_TEXTURE_PROCESS_TIMEOUT', 3600),
    'default_upscale_factor' => (int) env('GLB_TEXTURE_DEFAULT_UPSCALE', 2),
    'default_sharpen_amount' => (float) env('GLB_TEXTURE_DEFAULT_SHARPEN', 1.45),
    'default_contrast_factor' => (float) env('GLB_TEXTURE_DEFAULT_CONTRAST', 1.18),
    'default_color_factor' => (float) env('GLB_TEXTURE_DEFAULT_COLOR', 1.22),
    'default_brightness_factor' => (float) env('GLB_TEXTURE_DEFAULT_BRIGHTNESS', 1.04),
];
