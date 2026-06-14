<?php

use App\Models\Model3D;
use App\Models\ModelPart;
use App\Models\User;
use App\Models\UserTexture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);
});

test('authenticated user can generate new gemini textures and save them into my textures', function () {
    Storage::fake('public');

    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnXlqkAAAAASUVORK5CYII=';

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::sequence()
            ->push([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Variation 1 generated with woven premium detail.'],
                            [
                                'inlineData' => [
                                    'mimeType' => 'image/png',
                                    'data' => $pngBase64,
                                ],
                            ],
                        ],
                    ],
                ]],
            ], 200)
            ->push([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Variation 2 generated with softer luxury fabric detail.'],
                            [
                                'inlineData' => [
                                    'mimeType' => 'image/png',
                                    'data' => $pngBase64,
                                ],
                            ],
                        ],
                    ],
                ]],
            ], 200),
    ]);

    config([
        'texture_suggestions.provider' => 'gemini',
        'texture_suggestions.gemini.api_key' => 'test-gemini-key',
        'texture_suggestions.default_limit' => 2,
        'texture_suggestions.max_generated_textures' => 4,
        'texture_suggestions.storage_directory' => 'ai_generated',
    ]);

    [$user, $model, $part] = seedTextureSuggestionFixtures();

    $response = $this->actingAs($user)->postJson(route('texture-suggestions.suggest'), [
        'model3d_id' => $model->id,
        'model_part_id' => $part->id,
        'prompt' => 'premium woven navy textile with clean modern weave',
        'style' => 'luxury',
        'limit' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('provider.mode', 'gemini_generated')
        ->assertJsonPath('provider.name', 'Gemini')
        ->assertJsonPath('selected_part.name', 'Body')
        ->assertJsonCount(2, 'suggestions');

    expect(UserTexture::query()->count())->toBe(2);

    $textures = UserTexture::query()->orderBy('id')->get();

    expect($textures[0]->texture_path)->toContain('user_textures/user_'.$user->id.'/ai_generated/')
        ->and($textures[1]->texture_path)->toContain('user_textures/user_'.$user->id.'/ai_generated/');

    Storage::disk('public')->assertExists($textures[0]->texture_path);
    Storage::disk('public')->assertExists($textures[1]->texture_path);

    Http::assertSentCount(2);
});

test('authenticated user can generate new openrouter textures and save them into my textures', function () {
    Storage::fake('public');

    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnXlqkAAAAASUVORK5CYII=';

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => 'OpenRouter generated a square textile texture.',
                    'images' => [[
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:image/png;base64,'.$pngBase64,
                        ],
                    ]],
                ],
                'finish_reason' => 'stop',
            ]],
        ], 200),
    ]);

    config([
        'texture_suggestions.provider' => 'openrouter',
        'texture_suggestions.openrouter.api_key' => 'test-openrouter-key',
        'texture_suggestions.openrouter.model' => 'google/gemini-2.5-flash-image',
        'texture_suggestions.openrouter.endpoint' => 'https://openrouter.ai/api/v1',
        'texture_suggestions.default_limit' => 1,
        'texture_suggestions.storage_directory' => 'ai_generated',
    ]);

    [$user, $model, $part] = seedTextureSuggestionFixtures();

    $response = $this->actingAs($user)->postJson(route('texture-suggestions.suggest'), [
        'model3d_id' => $model->id,
        'model_part_id' => $part->id,
        'prompt' => 'premium woven navy textile with clean modern weave',
        'style' => 'luxury',
        'limit' => 1,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('provider.mode', 'openrouter_generated')
        ->assertJsonPath('provider.name', 'OpenRouter')
        ->assertJsonCount(1, 'suggestions');

    $texture = UserTexture::query()->first();

    expect($texture)->not->toBeNull()
        ->and($texture->texture_path)->toContain('user_textures/user_'.$user->id.'/ai_generated/');

    Storage::disk('public')->assertExists($texture->texture_path);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-openrouter-key')
            && $request['model'] === 'google/gemini-2.5-flash-image'
            && $request['modalities'] === ['image', 'text'];
    });
});

test('texture generation fails clearly when gemini is unavailable', function () {
    Storage::fake('public');

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'message' => 'Provider unavailable.',
            ],
        ], 500),
    ]);

    config([
        'texture_suggestions.provider' => 'gemini',
        'texture_suggestions.gemini.api_key' => 'test-gemini-key',
        'texture_suggestions.default_limit' => 1,
    ]);

    [$user, $model, $part] = seedTextureSuggestionFixtures();

    $response = $this->actingAs($user)->postJson(route('texture-suggestions.suggest'), [
        'model3d_id' => $model->id,
        'model_part_id' => $part->id,
        'prompt' => 'sleek black carbon detail',
        'style' => 'sport',
        'limit' => 1,
    ]);

    $response->assertStatus(503)
        ->assertJsonPath('success', false);

    expect(UserTexture::query()->count())->toBe(0);
});

/**
 * @return array{0: User, 1: Model3D, 2: ModelPart}
 */
function seedTextureSuggestionFixtures(): array
{
    $user = User::factory()->create();

    $model = Model3D::create([
        'user_id' => $user->id,
        'name' => 'Sport Jacket',
        'slug' => 'sport-jacket',
        'description' => 'Jaket 3D untuk eksplorasi material kain dan sporty look.',
        'age_category' => 'adult',
        'gender_category' => 'unisex',
        'model_path' => 'models/sport-jacket/model.glb',
        'model_format' => 'glb',
        'is_published' => true,
    ]);

    $part = ModelPart::create([
        'model3d_id' => $model->id,
        'part_name' => 'Body',
        'mesh_name' => 'BodyMesh',
    ]);

    return [$user, $model, $part];
}
