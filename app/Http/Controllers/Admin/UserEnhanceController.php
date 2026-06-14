<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlbTextureEnhancementProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UserEnhanceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Mengambil data project beserta relasi user dan model aslinya (jika ada)
        $projects = GlbTextureEnhancementProject::with(['user', 'model3d'])
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

        return view('admin.models.userenhanceresult', compact('projects'));
    }

    public function destroy($id)
    {
        $project = GlbTextureEnhancementProject::findOrFail($id);
        $uuid = $project->uuid;

        // 1. Hapus direktori Workspace (storage/app/glb_texture_runtime/{uuid})
        $workspaceRoot = rtrim((string) config('glb_texture_enhancement.working_root', storage_path('app/glb_texture_runtime')), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $uuid;
            
        if (File::isDirectory($workspaceRoot)) {
            File::deleteDirectory($workspaceRoot);
        }

        // 2. Hapus direktori Output Publik (storage/app/public/glb_texture_outputs/{uuid})
        $publicOutputDir = rtrim((string) config('glb_texture_enhancement.public_output_root', storage_path('app/public/glb_texture_outputs')), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $uuid;
            
        if (File::isDirectory($publicOutputDir)) {
            File::deleteDirectory($publicOutputDir);
        }

        // 3. Hapus direktori Input Upload (storage/app/public/enhancement_inputs/{uuid})
        $inputDir = Storage::disk('public')->path('enhancement_inputs' . DIRECTORY_SEPARATOR . $uuid);
        
        if (File::isDirectory($inputDir)) {
            File::deleteDirectory($inputDir);
        }

        // Hapus data project dari database
        $project->delete();

        return redirect()->route('admin.userenhance.index')
            ->with('success', 'Project Enhancement dan seluruh file terkait di server berhasil dihapus.');
    }
}