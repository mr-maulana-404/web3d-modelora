<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Model3D;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Syarat Utama: Harus dipublish
        $query = Model3D::with('owner')->where('is_published', true);

        if ($request->filled('search')) {
            $search = $request->search;

            // 2. Gunakan query grouping (tanda kurung dalam SQL)
            // Ini setara dengan: WHERE is_published = 1 AND (name LIKE ... OR owner name LIKE ...)
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('owner', function($innerQ) use ($search) {
                    $innerQ->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->wantsJson()) {
            $models = $query->latest()->get();
            return response()->json($models);
        }

        $models = $query->latest()->get();
        return view('app.dashboard', compact('models'));
    }
}