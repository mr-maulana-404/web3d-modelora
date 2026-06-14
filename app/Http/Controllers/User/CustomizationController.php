<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\System\CreditController;
use App\Models\AdminTexture;
use App\Models\CustomizationTexture;
use App\Models\Model3D;
use App\Models\ModelCustomization;
use App\Models\ModelPart;
use App\Models\UserTexture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomizationController extends Controller
{
    public function edit(Model3D $model3d)
    {
        $creditController = app(CreditController::class);
        $user = Auth::user();
        $creditBalance = $creditController->balance($user, CreditController::REGISTER_BONUS);
        $textureSuggestionCost = $creditController->texturePromptCost($user);
        $isCreditExempt = $creditController->isExempt($user);

        // part yang boleh dikustom
        $parts = ModelPart::where('model3d_id', $model3d->id)->get();

        // texture dari admin
        $adminTextures = AdminTexture::orderBy('category')
        ->orderBy('name')
        ->get()
        ->groupBy(function ($t) {
            return $t->category ?: 'General';
        });

        // texture milik user
        $userTextures = UserTexture::where('user_id', Auth::id())->latest()->get();

        return view('app.gallery.customize', compact(
            'model3d',
            'parts',
            'adminTextures',
            'userTextures',
            'creditBalance',
            'textureSuggestionCost',
            'isCreditExempt'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'model3d_id' => ['required', 'exists:model3ds,id'],
            'name' => ['required', 'string', 'max:255'],
            'textures' => ['required', 'array'],
            'textures.*.model_part_id' => ['required', 'exists:model_parts,id'],
            'textures.*.texture_type' => ['required', Rule::in(['admin', 'user', 'color'])],
            'textures.*.texture_path' => ['nullable', 'string', 'max:255'],
            'textures.*.color_value' => ['nullable', 'string', 'max:20'],
            'thumbnail' => ['required', 'string'],
        ]);

        DB::beginTransaction();

        try {

            // === SAVE THUMBNAIL ===
            $base64 = $request->thumbnail;
            $base64 = str_replace('data:image/png;base64,', '', $base64);
            $base64 = str_replace(' ', '+', $base64);

            $imageData = base64_decode($base64);

            $filename = 'custom_' . Str::uuid() . '.png';
            $path = "custom_thumbnails/" . $filename;

            Storage::disk('public')->put($path, $imageData);

            // === SAVE CUSTOMIZATION ===
            $customization = ModelCustomization::create([
                'user_id' => Auth::id(),
                'model3d_id' => $request->model3d_id,
                'name' => $request->name,
                'thumbnail_path' => $path,
            ]);

            foreach ($request->textures as $row) {

                CustomizationTexture::create([
                    'model_customization_id' => $customization->id,
                    'model_part_id' => $row['model_part_id'],
                    'texture_type' => $row['texture_type'],
                    'texture_path' => $row['texture_path'] ?? null,
                    'color_value' => $row['color_value'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'customization_id' => $customization->id,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
