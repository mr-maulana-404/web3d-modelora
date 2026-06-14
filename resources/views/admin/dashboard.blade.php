@extends('layouts.admin')

@section('content')
    <div class="admin-page admin-dashboard">
        <section class="admin-card admin-hero">
            <div>
                <div class="admin-page__eyebrow">Admin Workspace</div>
                <h1>Kelola Administrasi Website</h1>
                <p>Panel ini fokus untuk upload, publish, dan mengatur aset yang dipakai user saat menjelajahi serta meng-customize model 3D.</p>
            </div>
            <div class="admin-hero__mark" aria-hidden="true">
                <i class="fas fa-cubes"></i>
            </div>
        </section>

        <section class="admin-action-grid">
            <a class="admin-card admin-action-card" href="{{ route('admin.models.index') }}">
                <span class="admin-action-card__icon"><i class="fas fa-cube"></i></span>
                <span>
                    <h2>Model 3D</h2>
                    <p>Upload, edit metadata, generate thumbnail, dan publish model ke sisi user.</p>
                </span>
            </a>

            <a class="admin-card admin-action-card" href="{{ route('admin.textures.index') }}">
                <span class="admin-action-card__icon"><i class="fas fa-paint-brush"></i></span>
                <span>
                    <h2>App Texture</h2>
                    <p>Atur library material bawaan yang muncul pada halaman customize.</p>
                </span>
            </a>

            <a class="admin-card admin-action-card" href="{{ route('admin.user.textures') }}">
                <span class="admin-action-card__icon"><i class="fas fa-images"></i></span>
                <span>
                    <h2>User Texture</h2>
                    <p>Review dan hapus texture yang diupload atau digenerate oleh user.</p>
                </span>
            </a>
        </section>
    </div>
@endsection
