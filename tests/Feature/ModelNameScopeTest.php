<?php

use App\Models\Model3D;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);
});

test('different users can upload model files with the same model name', function () {
    Storage::fake('public');

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $this->actingAs($firstUser)
        ->post(route('gallery.store'), [
            'model_files' => [
                UploadedFile::fake()->createWithContent('same-model.glb', 'glTF'.str_repeat('A', 512)),
            ],
        ])
        ->assertRedirect(route('gallery'));

    $this->actingAs($secondUser)
        ->post(route('gallery.store'), [
            'model_files' => [
                UploadedFile::fake()->createWithContent('same-model.glb', 'glTF'.str_repeat('B', 512)),
            ],
        ])
        ->assertRedirect(route('gallery'));

    $models = Model3D::query()
        ->where('name', 'same-model')
        ->orderBy('user_id')
        ->get();

    expect($models)->toHaveCount(2)
        ->and($models->pluck('user_id')->all())->toBe([$firstUser->id, $secondUser->id])
        ->and($models->pluck('slug')->unique())->toHaveCount(2);
});

test('same user cannot upload duplicate model names', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('gallery.create'))
        ->post(route('gallery.store'), [
            'model_files' => [
                UploadedFile::fake()->createWithContent('same-model.glb', 'glTF'.str_repeat('A', 512)),
                UploadedFile::fake()->createWithContent('same-model.glb', 'glTF'.str_repeat('B', 512)),
            ],
        ])
        ->assertRedirect(route('gallery.create'))
        ->assertSessionHas('error');

    expect(Model3D::query()->where('user_id', $user->id)->count())->toBe(0);
});

test('admin upload also scopes duplicate model names by owner', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $admin = User::factory()->create(['usertype' => 'admin']);

    Model3D::create([
        'user_id' => $owner->id,
        'name' => 'shared-admin-name',
        'slug' => 'shared-admin-name',
        'model_path' => 'models/shared-admin-name.glb',
        'model_format' => 'glb',
        'is_published' => false,
        'processing_status' => 'ready',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.models.store'), [
            'model_files' => [
                UploadedFile::fake()->createWithContent('shared-admin-name.glb', 'glTF'.str_repeat('C', 512)),
            ],
        ])
        ->assertRedirect(route('admin.models.index'));

    $models = Model3D::query()
        ->where('name', 'shared-admin-name')
        ->orderBy('user_id')
        ->get();

    expect($models)->toHaveCount(2)
        ->and($models->pluck('user_id')->all())->toBe([$owner->id, $admin->id])
        ->and($models->pluck('slug')->unique())->toHaveCount(2);
});
