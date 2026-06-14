<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\System\CreditController;
use App\Models\ModelCustomization;
use Illuminate\Support\Facades\Auth;
use App\Models\AdminTexture;
use App\Models\UserTexture;
use App\Models\ModelPart;
use Illuminate\Support\Facades\Storage;

class SavedModelController extends Controller
{
    public function index()
    {
        $models = ModelCustomization::with([
                'model3d.parts',
                'textures.part',
            ])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('app.gallery.savedmodel', compact('models'));
    }

    public function edit(ModelCustomization $customization)
    {
        if ($customization->user_id !== Auth::id()) {
            abort(403);
        }

        $model3d = $customization->model3d;

        $parts = ModelPart::where('model3d_id', $model3d->id)->get();

        $adminTextures = AdminTexture::orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($t) => $t->category ?: 'General');

        $userTextures = UserTexture::where('user_id', Auth::id())
            ->latest()
            ->get();

        $creditController = app(CreditController::class);
        $user = Auth::user();
        $creditBalance = $creditController->balance($user, CreditController::REGISTER_BONUS);
        $textureSuggestionCost = $creditController->texturePromptCost($user);
        $isCreditExempt = $creditController->isExempt($user);
        
        $customization->update([
            'last_opened_at' => now()
        ]);

        return view('app.gallery.customize', [
            'model3d' => $model3d,
            'parts' => $parts,
            'adminTextures' => $adminTextures,
            'userTextures' => $userTextures,
            'savedCustomization' => $customization->load('textures'),
            'creditBalance' => $creditBalance,
            'textureSuggestionCost' => $textureSuggestionCost,
            'isCreditExempt' => $isCreditExempt,
        ]);
    }

    public function destroy(ModelCustomization $customization)
    {
        if ($customization->user_id !== Auth::id()) {
            abort(403);
        }

        // hapus thumbnail
        if ($customization->thumbnail_path) {
            Storage::disk('public')->delete($customization->thumbnail_path);
        }

        $customization->textures()->delete();
        $customization->delete();

        return back()->with('success', 'Saved model deleted.');
    }
}
