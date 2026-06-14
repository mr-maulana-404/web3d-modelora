<?php

return [
    'api_key' => env('MESHY_API_KEY'),
    'api_endpoint' => env('MESHY_API_ENDPOINT', 'https://api.meshy.ai'),
    'timeout' => (int) env('MESHY_TIMEOUT', 120),
    'poll_interval' => (int) env('MESHY_POLL_INTERVAL', 8),
    'max_wait_seconds' => (int) env('MESHY_MAX_WAIT_SECONDS', 1800),
    'default_retexture_prompt' => env(
        'MESHY_DEFAULT_RETEXTURE_PROMPT',
        'Preserve the EXACT original colors, skin tones, fabric colors, clothing details, and facial identity from the scan. Only fill mesh holes and repair torn areas so they blend seamlessly with original textures. DO NOT change any colors, DO NOT alter clothing, DO NOT modify the persons appearance. Output must look identical to input but with all mesh damage repaired.'
    ),
];