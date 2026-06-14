@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <!-- Header -->
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">Model Controls</div>
            <h3 class="admin-page__title">Edit Model</h3>
            <p class="admin-page__subtitle">Perbarui metadata dan thumbnail model.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.models.index') }}">App Model</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-body"> 
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('admin.models.update', $model->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label>Nama</label>
                    <input class="form-control" type="text" name="name" value="{{ old('name', $model->name) }}">
                </div>

                <div class="mb-3">
                    <label>Slug</label>
                    <input class="form-control" type="text" name="slug" value="{{ old('slug', $model->slug) }}">
                </div>

                <div class="mb-3">
                    <label for="age_category" class="form-label">Age Category</label>
                    <select class="form-select @error('age_category') is-invalid @enderror" name="age_category" id="age_category">
                        <option value="" selected disabled>< Select Age ></option>
                        <option value="kid" {{ old('age_category') == 'kid' ? 'selected' : '' }}>Kid</option>
                        <option value="teen" {{ old('age_category') == 'teen' ? 'selected' : '' }}>Teen</option>
                        <option value="adult" {{ old('age_category') == 'adult' ? 'selected' : '' }}>Adult</option>
                    </select>
                    @error('age_category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="gender_category" class="form-label">Gender Category</label>
                    <select class="form-select @error('gender_category') is-invalid @enderror" name="gender_category" id="gender_category">
                        <option value="" selected disabled>< Select Gender ></option>
                        <option value="male" {{ old('gender_category') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender_category') == 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender_category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control">{{ old('description', $model->description) }}</textarea>
                </div>
                
                <div class="vstack gap-1 mb-3">
                    <label>Ganti Thumbnail</label>
                    <input class="form-control" type="file" name="thumbnail" accept=".jpg, .jpeg, .png, .webp">
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.models.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-warning" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
