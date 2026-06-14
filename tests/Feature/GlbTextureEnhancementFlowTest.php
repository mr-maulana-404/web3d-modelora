<?php

use App\Jobs\ProcessGlbTextureEnhancementProject;
use App\Models\GlbTextureEnhancementProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);
});

test('authenticated user can upload and queue a model enhancement job', function () {
    Storage::fake('public');
    Queue::fake();

    $user = User::factory()->create();

    $glbFile = UploadedFile::fake()->createWithContent(
        'chair.glb',
        'glTF'.str_repeat('A', 512)
    );

    $this->actingAs($user)
        ->post(route('gallery.enhancement.store'), [
            'name' => 'Chair Enhancement',
            'description' => 'Improve GLB textures.',
            'mode' => 'local_texture',
            'model_file' => $glbFile,
        ])
        ->assertRedirect(route('gallery.enhancement.index'));

    $project = GlbTextureEnhancementProject::query()->firstOrFail();

    expect($project->status)->toBe('processing')
        ->and($project->pipeline_stage)->toBe('uploaded')
        ->and($project->progress)->toBe(0)
        ->and($project->input_glb_path)->not->toBeNull();

    Storage::disk('public')->assertExists(
        'enhancement_inputs/'.$project->uuid.'/original.glb'
    );

    Queue::assertPushed(ProcessGlbTextureEnhancementProject::class, function ($job) use ($project) {
        return $job->projectId === $project->id;
    });

    $pollResponse = $this->actingAs($user)
        ->getJson(route('gallery.enhancement.poll', $project));

    $pollResponse->assertOk()
        ->assertJsonPath('status', 'processing')
        ->assertJsonPath('pipeline_stage', 'uploaded')
        ->assertJsonPath('progress', 0)
        ->assertJsonStructure(['output_glb_url'])
        ->assertJsonPath('input_glb_path', 'enhancement_inputs/'.$project->uuid.'/original.glb');
});

test('upload rejects invalid glb signature', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $invalidFile = UploadedFile::fake()->createWithContent(
        'broken.glb',
        'NOPE'.str_repeat('B', 128)
    );

    $this->actingAs($user)
        ->from(route('gallery.enhancement.index'))
        ->post(route('gallery.enhancement.store'), [
            'mode' => 'local_texture',
            'model_file' => $invalidFile,
        ])
        ->assertRedirect(route('gallery.enhancement.index'))
        ->assertSessionHasErrors('model_file');

    expect(GlbTextureEnhancementProject::count())->toBe(0);
});
