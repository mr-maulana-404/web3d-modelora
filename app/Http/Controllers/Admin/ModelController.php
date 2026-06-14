<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Model3D;
use App\Models\ModelCustomization;
use App\Models\ModelPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class ModelController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $models = Model3D::when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.models.index', compact('models', 'search'));
    }

    public function create()
    {
        return view('admin.models.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'model_files' => ['required','array'],
            'model_files.*' => ['file','max:51200'],
            'description' => ['nullable','string'],
            'age_category' => ['nullable','string'],
            'gender_category' => ['nullable','string']
        ]);

        DB::beginTransaction();

        try {
            $generatedModels = [];

            $uploadedNames = [];

            foreach ($request->file('model_files') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ownerId = Auth::id();
                $normalizedName = Str::lower($originalName);

                if (in_array($normalizedName, $uploadedNames, true) || $this->modelNameExistsForOwner($originalName, $ownerId)) {
                    throw new \Exception("You already have a model named \"{$originalName}\".");
                }

                $uploadedNames[] = $normalizedName;
                $slug = $this->makeUniqueSlug($originalName, $ownerId);
                $folderName = $slug . '_' . Str::random(5);

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, ['zip','glb'])) {
                    throw new \Exception("Model format must be ZIP or GLB");
                }

                $modelPath = null;
                $modelFormat = null;
                $gltfPathForParsing = null;

                // HANDLE ZIP (GLTF)
                if ($extension === 'zip') {

                    $modelFormat = 'gltf';

                    $extractPath = storage_path("app/public/models/{$folderName}");

                    if (is_dir($extractPath)) {
                        $this->deleteDirectory($extractPath);
                    }

                    mkdir($extractPath, 0755, true);

                    $zipTempPath = storage_path("app/tmp_{$folderName}.zip");
                    $file->move(storage_path('app'), "tmp_{$folderName}.zip");

                    $zip = new ZipArchive;

                    if ($zip->open($zipTempPath) !== true) {
                        throw new \Exception("Failed to open ZIP: {$originalName}");
                    }

                    $zip->extractTo($extractPath);
                    $zip->close();
                    unlink($zipTempPath);

                    $gltfFiles = glob($extractPath . '/*.gltf');

                    if (count($gltfFiles) === 0) {
                        throw new \Exception("GLTF file not found in ZIP {$originalName}");
                    }

                    $gltfPath = $gltfFiles[0];

                    $gltfFilename = basename($gltfPath);
                    $modelPath = "models/{$folderName}/{$gltfFilename}";

                    $gltfPathForParsing = $gltfPath;
                }

                // HANDLE GLB
                elseif ($extension === 'glb') {

                    $modelFormat = 'glb';

                    $storedPath = $file->storeAs(
                        "models",
                        "{$folderName}.glb",
                        'public'
                    );

                    $modelPath = $storedPath;
                }

                // SAVE MODEL DB
                $model = Model3D::create([
                    'user_id' => $ownerId,
                    'name' => $originalName,
                    'slug' => $slug,
                    'description' => $request->description,
                    'age_category' => $request->age_category,
                    'gender_category' => $request->gender_category,
                    'model_path' => $modelPath,
                    'model_format' => $modelFormat,
                    'is_published' => false,
                    'thumbnail_path' => null,
                    'processing_status' => 'processing'
                ]);

                $generatedModels[] = [
                    'id' => $model->id,
                    'path' => asset("storage/".$modelPath)
                ];

                // AUTO GENERATE model_parts (ONLY FOR GLTF ZIP)
                if ($modelFormat === 'gltf' && $gltfPathForParsing) {

                    $meshNames = $this->extractMeshNamesFromGltf($gltfPathForParsing);

                    foreach ($meshNames as $meshName) {
                        ModelPart::create([
                            'model3d_id' => $model->id,
                            'part_name' => $meshName,
                            'mesh_name' => $meshName,
                        ]);
                    }
                }
            }

            DB::commit();

            // Jika ZIP → langsung selesai (parts sudah dibuat backend)
            return redirect()
                ->route('admin.models.index')
                ->with('processing_models', $generatedModels)
                ->with('success', 'Models uploaded successfully!');
        }

        catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit(Model3D $model)
    {
        return view('admin.models.edit', compact('model'));
    }

    public function update(Request $request, Model3D $model)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('model3ds', 'name')
                    ->where(fn ($query) => $model->user_id
                        ? $query->where('user_id', $model->user_id)
                        : $query->whereNull('user_id'))
                    ->ignore($model->id),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'age_category' => ['nullable', 'string', 'max:100'],
            'gender_category' => ['nullable', 'string', 'max:100'],

            'thumbnail' => ['nullable', 'image', 'max:5120'],
        ]);

        $slug = $this->makeUniqueSlug($request->slug ?: $request->name, $model->user_id, $model->id);

        // thumbnail update
        if ($request->hasFile('thumbnail')) {

            // hapus thumbnail lama jika ada
            if ($model->thumbnail_path) {
                Storage::disk('public')->delete($model->thumbnail_path);
            }

            $thumbnailPath = $request->file('thumbnail')->storeAs(
                'model_thumbnails',
                'model_' . $model->id . '.' . $request->file('thumbnail')->extension(),
                'public'
            );

            $model->thumbnail_path = $thumbnailPath;
        }

        $model->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'age_category' => $request->age_category,
            'gender_category' => $request->gender_category,
        ]);

        return redirect()
            ->route('admin.models.index')
            ->with('success', 'Model berhasil diupdate.');
    }

    public function destroy(Model3D $model)
    {
        // hapus folder model dari storage
        $folderModel = dirname($model->model_path);
        Storage::disk('public')->deleteDirectory($folderModel);

        // hapus thumbnail
        if ($model->thumbnail_path) {
            Storage::disk('public')->delete($model->thumbnail_path);
        }

        // Ambil semua data customization anak dari model ini
        $customizations = ModelCustomization::where('model3d_id', $model->id)->get();
        foreach ($customizations as $customization) {
            // Jika ada file thumbnail-nya, hapus dari storage
            if ($customization->thumbnail_path) {
                Storage::disk('public')->delete($customization->thumbnail_path);
            }
        }

        $model->delete();

        return redirect()
            ->route('admin.models.index')
            ->with('success', 'The model and all associated customization data were successfully deleted.');
    }

    public function generateParts(Request $request, Model3D $model)
    {
        $request->validate([
            'meshes' => ['required', 'array'],
            'meshes.*' => ['string']
        ]);

        // hapus part lama (kalau ada)
        $model->parts()->delete();

        foreach ($request->meshes as $meshName) {
            ModelPart::create([
                'model3d_id' => $model->id,
                'part_name' => $meshName,
                'mesh_name' => $meshName,
            ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function saveThumbnail(Request $request, Model3D $model)
    {
        $request->validate([
            'thumbnail' => ['required', 'string']
        ]);

        $base64 = $request->thumbnail;
        $base64 = str_replace('data:image/png;base64,', '', $base64);
        $base64 = str_replace(' ', '+', $base64);

        $imageData = base64_decode($base64);

        $filename = 'model_' . $model->id . '_' . Str::uuid() . '.png';
        $path = "model_thumbnails/" . $filename;

        Storage::disk('public')->put($path, $imageData);

        // hapus thumbnail lama jika ada
        if ($model->thumbnail_path) {
            Storage::disk('public')->delete($model->thumbnail_path);
        }

        $model->update([
            'thumbnail_path' => $path
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function markReady(Model3D $model)
    {
        $model->update([
            'processing_status' => 'ready'
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    // toggle untuk publish atau tidak
    public function toggle(Model3D $model)
    {
        $model->update(['is_published' => !$model->is_published]);
        return back();
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }    

    private function extractMeshNamesFromGltf(string $gltfPath): array
    {
        $json = file_get_contents($gltfPath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['nodes'])) {
            return [];
        }

        $meshNames = [];

        foreach ($data['nodes'] as $node) {
            // kita hanya ambil node yang punya mesh
            if (!isset($node['mesh'])) continue;

            $name = $node['name'] ?? null;

            if (!$name || trim($name) === '') {
                $name = 'Mesh_' . (count($meshNames) + 1);
            }

            $meshNames[] = $name;
        }

        // buang duplikat
        return array_values(array_unique($meshNames));
    }

    private function modelNameExistsForOwner(string $name, ?int $userId, ?int $ignoreId = null): bool
    {
        return Model3D::query()
            ->when(
                $userId,
                fn ($query) => $query->where('user_id', $userId),
                fn ($query) => $query->whereNull('user_id')
            )
            ->where('name', $name)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function makeUniqueSlug(string $value, ?int $userId, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'model-3d';
        $slug = $baseSlug;
        $counter = 2;

        if ($this->slugExists($slug, $ignoreId) && $userId) {
            $slug = "{$baseSlug}-user-{$userId}";
        }

        while ($this->slugExists($slug, $ignoreId)) {
            $suffix = $userId ? "user-{$userId}-{$counter}" : (string) $counter;
            $slug = "{$baseSlug}-{$suffix}";
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Model3D::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

}
