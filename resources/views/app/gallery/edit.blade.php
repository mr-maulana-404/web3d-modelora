@extends('layouts.app')
@section('title', 'User Model Edit')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                
                <a href="{{ route('home') }}" class="navbar-brand text-decoration-none">
                    <img src="{{ asset('storage/img/modelora_logo_dark.png') }}" alt="MOD3LORA" class="nav-logo logo-default" style="height: 40px; width: auto; object-fit: contain;">
                </a>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('gallery') }}" class="text-decoration-none text-muted">User Gallery</a></li>
                        <li class="breadcrumb-item active fw-bold" aria-current="page">Edit Data Model</li>
                    </ol>
                </nav>
                
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 24px; overflow: hidden; background: #ffffff;">
                <div class="row g-0">
                    
                    <!-- Sisi Kiri: Preview & Status -->
                    <div class="col-md-4 bg-light border-end p-4 d-flex flex-column align-items-center justify-content-center text-center">
                        <div class="mb-4">
                            <h5 class="fw-bold mb-1">Current Thumbnail</h5>
                            <p class="text-muted small">Tampilan saat ini di gallery</p>
                            <div class="position-relative mt-3">
                                @if($model->thumbnail_path)
                                    <img src="{{ asset('storage/' . $model->thumbnail_path) }}" 
                                         alt="Thumbnail" 
                                         class="img-fluid shadow-sm" 
                                         style="border-radius: 15px; width: 100%; aspect-ratio: 1/1; object-fit: cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-secondary-subtle" 
                                         style="width: 200px; height: 200px; border-radius: 15px;">
                                        <i class="fa-solid fa-image fa-3x text-muted"></i>
                                    </div>
                                @endif
                                <span class="badge {{ $model->is_published ? 'bg-success' : 'bg-warning' }} position-absolute top-0 end-0 m-2 shadow-sm">
                                    {{ $model->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-2 w-100 p-3 bg-white rounded-4 border border-dashed">
                            <small class="text-muted d-block">Model Format</small>
                            <span class="badge bg-dark mt-1 text-uppercase px-3">{{ $model->model_format }}</span>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Form Input -->
                    <div class="col-md-8 p-4 p-md-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-warning rounded-3 p-2 me-3 shadow-sm">
                                <i class="fa-solid fa-pen-to-square text-white"></i>
                            </div>
                            <h3 class="fw-bold mb-0">Model Information</h3>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger border-0 rounded-4 mb-4">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('gallery.update', $model->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PUT')

                            <div class="row">
                                <!-- Nama & Slug -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold small">Model Name</label>
                                    <input class="form-control custom-input" type="text" name="name" value="{{ old('name', $model->name) }}" placeholder="e.g. Human Character">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold small">URL Slug</label>
                                    <input class="form-control custom-input bg-light" type="text" name="slug" value="{{ old('slug', $model->slug) }}" readonly>
                                </div>

                                <!-- Category Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="age_category" class="form-label fw-semibold small">Age Category</label>
                                    <select class="form-select custom-input @error('age_category') is-invalid @enderror" name="age_category" id="age_category">
                                        <option value="" disabled {{ is_null($model->age_category) ? 'selected' : '' }}>Select Age</option>
                                        <option value="Kid" {{ old('age_category', $model->age_category) == 'Kid' ? 'selected' : '' }}>Kid</option>
                                        <option value="Teen" {{ old('age_category', $model->age_category) == 'Teen' ? 'selected' : '' }}>Teen</option>
                                        <option value="Adult" {{ old('age_category', $model->age_category) == 'Adult' ? 'selected' : '' }}>Adult</option>
                                        <option value="Unknown" {{ old('age_category', $model->age_category) == 'Unknown' ? 'selected' : '' }}>Unknown</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gender_category" class="form-label fw-semibold small">Gender Category</label>
                                    <select class="form-select custom-input @error('gender_category') is-invalid @enderror" name="gender_category" id="gender_category">
                                        <option value="" disabled {{ is_null($model->gender_category) ? 'selected' : '' }}>Select Gender</option>
                                        <option value="Male" {{ old('gender_category', $model->gender_category) == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender_category', $model->gender_category) == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Unknown" {{ old('gender_category', $model->gender_category) == 'Unknown' ? 'selected' : '' }}>Unknown</option>
                                    </select>
                                </div>

                                <!-- Description -->
                                <div class="col-12 mb-4">
                                    <label class="form-label fw-semibold small">Description</label>
                                    <textarea name="description" class="form-control custom-input" rows="4" placeholder="Tell more about this 3D model...">{{ old('description', $model->description) }}</textarea>
                                </div>
                                
                                <!-- File Upload -->
                                <div class="col-12 mb-4">
                                    <label class="form-label fw-semibold small">Update Thumbnail Image</label>
                                    <div class="input-group">
                                        <input class="form-control custom-input" type="file" name="thumbnail" accept=".jpg, .jpeg, .png, .webp">
                                    </div>
                                    <div class="form-text mt-2 small">Recommended size: 800x480px. JPG, PNG, or WEBP.</div>
                                </div>

                                <!-- Publish Toggle -->
                                <div class="col-12 mb-4">
                                    <div class="form-check form-switch custom-switch p-3 bg-light rounded-3 d-flex align-items-center">
                                        <input type="checkbox"
                                            class="form-check-input ms-0 me-3"
                                            name="is_published"
                                            id="is_published"
                                            value="1"
                                            style="width: 3em; height: 1.5em; cursor: pointer;"
                                            {{ $model->is_published ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold mb-0" for="is_published" style="cursor: pointer;">
                                            Publish model to public gallery
                                            <span class="d-block text-muted fw-normal small">Making this model visible for everyone to see and download.</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex justify-content-end gap-2 pt-4 border-top">
                                <a href="{{ route('gallery') }}" class="btn btn-light px-4 py-2 fw-semibold rounded-3 text-muted">Cancel</a>
                                <button class="btn btn-warning px-5 py-2 fw-bold text-dark rounded-3 shadow-sm border-0" type="submit" style="background: #F7BA2C;">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; }
    
    .custom-input {
        border-radius: 12px !important;
        padding: 12px 16px;
        border: 1.5px solid #E0E0E0;
        transition: all 0.3s ease;
    }

    .custom-input:focus {
        border-color: #F7BA2C;
        box-shadow: 0 0 0 4px rgba(247, 186, 44, 0.1);
    }

    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 1.2rem;
        line-height: 1;
        vertical-align: middle;
    }

    .custom-switch .form-check-input:checked {
        background-color: #F7BA2C;
        border-color: #F7BA2C;
    }

    .border-dashed {
        border: 2px dashed #e9ecef !important;
    }
</style>
@endsection