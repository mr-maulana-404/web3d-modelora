<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserTexture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextureController extends Controller
{
    public function index()
    {
        $textures = UserTexture::where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'textures' => $textures->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'texture_path' => $t->texture_path,
                    'url' => Storage::disk('public')->url($t->texture_path),
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'texture' => ['required', 'image', 'max:5120'], // 5MB
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = Auth::id();
        $file = $request->file('texture');

        $safeName = $request->name
            ? Str::slug($request->name)
            : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        $filename = $safeName . '-' . time() . '.' . $file->extension();

        $path = $file->storeAs(
            "user_textures/user_{$userId}",
            $filename,
            'public'
        );

        $texture = UserTexture::create([
            'user_id' => $userId,
            'name' => $request->name ?? $safeName,
            'texture_path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'texture' => [
                'id' => $texture->id,
                'name' => $texture->name,
                'texture_path' => $texture->texture_path,
                'url' => Storage::disk('public')->url($texture->texture_path),
            ]
        ]);
    }

    public function destroy(UserTexture $texture)
    {
        // security: pastikan milik user ini
        if ($texture->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        // hapus file
        Storage::disk('public')->delete($texture->texture_path);

        // hapus DB
        $texture->delete();

        return response()->json([
            'success' => true,
            'message' => 'Texture berhasil dihapus.'
        ]);
    }
}
