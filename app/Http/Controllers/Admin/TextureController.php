<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTexture;
use App\Models\UserTexture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextureController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $textures = AdminTexture::when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return view('admin.textures.index', compact('textures', 'search'));
    }

    public function create()
    {
        return view('admin.textures.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'texture' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $file = $request->file('texture');

        $safeName = Str::slug($request->name);
        $ext = $file->extension();

        // simpan di storage/app/public/for_textures/
        $path = $file->storeAs(
            'for_textures',
            $safeName . '.' . $ext,
            'public'
        );

        AdminTexture::create([
            'name' => $request->name,
            'category' => $request->category,
            'texture_path' => $path,
        ]);

        return redirect()
            ->route('admin.textures.index')
            ->with('success', 'Texture berhasil ditambahkan.');
    }

    public function edit(AdminTexture $texture)
    {
        return view('admin.textures.edit', compact('texture'));
    }

    public function update(Request $request, AdminTexture $texture)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'texture' => ['nullable', 'image', 'max:5120'],
        ]);

        // update file jika ada upload baru
        if ($request->hasFile('texture')) {

            // hapus file lama
            if ($texture->texture_path) {
                Storage::disk('public')->delete($texture->texture_path);
            }

            $file = $request->file('texture');
            $safeName = Str::slug($request->name);
            $ext = $file->extension();

            $path = $file->storeAs(
                'for_textures',
                $safeName . '.' . $ext,
                'public'
            );

            $texture->texture_path = $path;
        }

        $texture->update([
            'name' => $request->name,
            'category' => $request->category,
            'texture_path' => $texture->texture_path,
        ]);

        return redirect()
            ->route('admin.textures.index')
            ->with('success', 'Texture berhasil diupdate.');
    }

    public function destroy(AdminTexture $texture)
    {
        if ($texture->texture_path) {
            Storage::disk('public')->delete($texture->texture_path);
        }

        $texture->delete();

        return redirect()
            ->route('admin.textures.index')
            ->with('success', 'Texture berhasil dihapus.');
    }

    public function usertextureindex(Request $request)
    {
        $search = $request->input('search');

        $textures = UserTexture::with('user')
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('user', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('admin.textures.user', compact('textures', 'search'));
    }

    public function deleteusertexture(UserTexture $texture)
    {
        $user = Auth::user();

        // Security: Izinkan jika milik user tersebut ATAU jika user adalah admin
        if ($texture->user_id !== $user->id && $user->usertype !== 'admin') {
            // Ganti JSON menjadi Redirect Back dengan Error
            return redirect()->back()->with('error', 'Unauthorized. Anda tidak memiliki izin.');
        }

        // Hapus file fisik dari storage
        if ($texture->texture_path) {
            Storage::disk('public')->delete($texture->texture_path);
        }

        // Hapus data dari database
        $texture->delete();

        return redirect()
            ->route('admin.user.textures')
            ->with('success', 'Texture berhasil dihapus.');
    }
}
