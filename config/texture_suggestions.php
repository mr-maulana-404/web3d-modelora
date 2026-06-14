<?php

$geminiEndpoint = (string) env('GEMINI_API_ENDPOINT', '');
$geminiModel = (string) env('GEMINI_MODEL', '');

return [
    'enabled' => env('TEXTURE_SUGGESTIONS_ENABLED', true),
    'provider' => env('TEXTURE_SUGGESTIONS_PROVIDER', 'gemini'),
    'timeout' => (int) env('TEXTURE_SUGGESTIONS_TIMEOUT', 90),
    'connect_timeout' => (int) env('TEXTURE_SUGGESTIONS_CONNECT_TIMEOUT', 20),
    'request_time_limit' => (int) env('TEXTURE_SUGGESTIONS_REQUEST_TIME_LIMIT', 180),
    'default_limit' => (int) env('TEXTURE_SUGGESTIONS_DEFAULT_LIMIT', 3),
    'max_generated_textures' => (int) env('TEXTURE_SUGGESTIONS_MAX_GENERATED_TEXTURES', 4),
    'max_inline_image_bytes' => (int) env('TEXTURE_SUGGESTIONS_MAX_INLINE_IMAGE_BYTES', 2097152),
    'storage_directory' => env('TEXTURE_SUGGESTIONS_STORAGE_DIRECTORY', 'ai_generated'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-image-preview'),
        'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY', env('GEMINI_API_KEY')),
        'model' => env(
            'OPENROUTER_MODEL',
            str_starts_with($geminiModel, 'google/')
                ? $geminiModel
                : 'google/gemini-2.5-flash-image'
        ),
        'endpoint' => env(
            'OPENROUTER_API_ENDPOINT',
            str_contains($geminiEndpoint, 'openrouter.ai')
                ? $geminiEndpoint
                : 'https://openrouter.ai/api/v1'
        ),
        'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL')),
        'app_name' => env('OPENROUTER_APP_NAME', env('APP_NAME', 'Laravel')),
    ],
];
