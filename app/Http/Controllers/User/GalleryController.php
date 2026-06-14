<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Model3D;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use ZipArchive;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = Model3D::query()
            ->where('user_id', Auth::id())
            ->with('owner');

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // FILTER AGE
        if ($request->filled('age')) {
            $query->where('age_category', $request->age);
        }

        // FILTER GENDER
        if ($request->filled('gender')) {
            $query->where('gender_category', $request->gender);
        }

        $models = $query->latest()->paginate(12)->withQueryString();

        return view('app.gallery.index', compact('models'));
    }

    public function customize(Model3D $model3d)
    {
        return view('app.gallery.customize', compact('model3d'));
    }
}
