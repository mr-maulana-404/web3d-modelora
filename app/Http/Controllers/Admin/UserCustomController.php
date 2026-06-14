<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelCustomization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserCustomController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Mengambil data kustomisasi beserta relasi user dan model aslinya
        $customizations = ModelCustomization::with(['user', 'model3d'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('model3d', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10);

        return view('admin.models.usercustomresult', compact('customizations'));
    }

    public function destroy($id)
    {
        $customization = ModelCustomization::findOrFail($id);

        // Hapus file thumbnail fisik dari storage public/custom_thumbnails
        if ($customization->thumbnail_path && Storage::disk('public')->exists($customization->thumbnail_path)) {
            Storage::disk('public')->delete($customization->thumbnail_path);
        }

        // Hapus data kustomisasi (data di tabel CustomizationTexture otomatis akan terhapus 
        // jika di database Anda sudah diset onDelete('cascade'), jika belum kita hapus manual)
        $customization->textures()->delete(); 
        $customization->delete();

        return redirect()->route('admin.usercustom.index')
            ->with('success', 'Data kustomisasi dan thumbnail berhasil dihapus.');
    }
}