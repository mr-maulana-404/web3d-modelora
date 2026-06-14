@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <!-- Header -->
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">Texture Controls</div>
            <h3 class="admin-page__title">Edit Texture</h3>
            <p class="admin-page__subtitle">Perbarui nama, kategori, atau file texture.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.textures.index') }}">App Texture</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.textures.update', $texture->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $texture->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="category" class="form-control"
                        value="{{ old('category', $texture->category) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Preview Texture</label><br>
                    <img src="{{ asset('storage/' . $texture->texture_path) }}"
                        width="120" height="120"
                        style="object-fit:cover; border-radius:10px;">
                </div>

                <div class="mb-3">
                    <label class="form-label">Ganti Texture (opsional)</label>
                    <input type="file" name="texture" class="form-control" accept=".jpg, .jpeg, .png, .webp">
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.textures.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-warning" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
