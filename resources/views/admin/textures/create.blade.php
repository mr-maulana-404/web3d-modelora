@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <!-- Header -->
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">Texture Controls</div>
            <h3 class="admin-page__title">Add Texture</h3>
            <p class="admin-page__subtitle">Tambahkan material bawaan untuk dipakai user saat customize.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.textures.index') }}">App Texture</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
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
            <form action="{{ route('admin.textures.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="texture_category" class="form-label">Texture Category</label>
                    <select name="category" class="form-select @error('texture_category') is-invalid @enderror" id="texture_category" required>
                        <option value="" disabled {{ old('category') ? '' : 'selected' }}>< Select Category ></option>
                        <option value="skin" {{ old('category') == 'skin' ? 'selected' : '' }}>Skin</option>
                        <option value="hair" {{ old('category') == 'hair' ? 'selected' : '' }}>Hair</option>
                        <option value="fabric" {{ old('category') == 'fabric' ? 'selected' : '' }}>Fabric</option>
                        <option value="leather" {{ old('category') == 'leather' ? 'selected' : '' }}>Leather</option>
                        <option value="metal" {{ old('category') == 'metal' ? 'selected' : '' }}>Metal</option>
                        <option value="glass" {{ old('category') == 'glass' ? 'selected' : '' }}>Glass</option>
                        <option value="plastic" {{ old('category') == 'plastic' ? 'selected' : '' }}>Plastic</option>
                    </select>
                    @error('texture_category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Texture (.jpg, .jpeg, .png, .webp)</label>
                    <input type="file" name="texture" class="form-control" accept=".jpg, .jpeg, .png, .webp" required>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.textures.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary" type="submit">Upload</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
