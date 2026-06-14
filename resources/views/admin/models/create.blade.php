@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <!-- Header -->
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">Model Controls</div>
            <h3 class="admin-page__title">Add Model3D</h3>
            <p class="admin-page__subtitle">Upload file GLTF zip atau GLB untuk masuk ke library model.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.models.index') }}">App Model</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <!-- Alert -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('admin.models.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Model Files (GLTF .zip / GLB .glb)</label>

                    <div id="dropZone" class="drop-zone">
                        <p class="mb-1">Drag & Drop files here</p>
                        <p class="text-muted small">or click to browse</p>

                        <input 
                            type="file" 
                            id="modelFilesInput"
                            name="model_files[]" 
                            multiple 
                            accept=".zip,.glb" 
                            required
                            hidden
                        >
                    </div>

                    <div id="filePreviewList" class="mt-3"></div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.models.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary" type="submit">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
    const dropZone = document.getElementById('dropZone');
    const input = document.getElementById('modelFilesInput');
    const previewList = document.getElementById('filePreviewList');

    // click to open
    dropZone.addEventListener('click', () => input.click());

    // drag over
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('active');
    });

    // drag leave
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('active');
    });

    // drop
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('active');
        input.files = e.dataTransfer.files;
        renderPreview(input.files);
    });

    // input change
    input.addEventListener('change', () => {
        renderPreview(input.files);
    });

    // preview renderer
    function renderPreview(files) {
        previewList.innerHTML = '';

        Array.from(files).forEach(file => {

            const ext = file.name.split('.').pop().toLowerCase();
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);

            const badgeClass = ext === 'glb' ? 'badge-glb' : 'badge-zip';

            const item = document.createElement('div');
            item.className = 'file-preview-item';

            item.innerHTML = `
                <div>
                    <div>${file.name}</div>
                    <div class="text-muted small">${sizeMB} MB</div>
                </div>
            `;

            previewList.appendChild(item);
        });
    }

    // Loading indicator on submit
    const form = document.querySelector('form');

    form.addEventListener('submit', () => {

        const btn = form.querySelector("button[type='submit']");
        btn.disabled = true;

        const loading = document.createElement('div');
        loading.innerHTML = `
            <div class="text-center mt-3">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Uploading & Processing...</p>
            </div>
        `;

        form.appendChild(loading);
    });
    </script>
@endpush
