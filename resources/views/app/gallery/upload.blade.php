@extends('layouts.app')
@section('title', 'User Model Upload')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Header & Breadcrumb -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('home') }}" class="navbar-brand">
                    <img src="{{ asset('storage/img/modelora_logo_dark.png') }}" alt="MOD3LORA" height="35">
                </a>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('gallery') }}" class="text-decoration-none text-muted">User Gallery</a></li>
                        <li class="breadcrumb-item active fw-bold" aria-current="page">Upload Model</li>
                    </ol>
                </nav>
            </div>

            <!-- Upload Card -->
            <div class="card border-0 shadow-sm" style="border-radius: 24px; overflow: hidden; background: #ffffff;">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-1">Upload 3D Models</h3>
                        <p class="text-muted small mb-0">Upload your .GLB or .ZIP (containing GLTF) files here.</p>
                        <p class="text-muted small">*make sure you have partitioned the parts of the model you want to customize.</p>
                    </div>

                    <!-- Alert -->
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 rounded-4 mb-4 shadow-sm">
                            <ul class="mb-0 small fw-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('gallery.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <!-- Dropzone Area -->
                        <div class="mb-4">
                            <div id="dropZone" class="drop-zone p-5 text-center bg-light rounded-4 border-dashed transition-all">
                                <div class="drop-zone-content">
                                    <div class="icon-circle bg-white shadow-sm mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; border-radius: 50%;">
                                        <i class="fa-solid fa-cloud-arrow-up fs-2 text-warning"></i>
                                    </div>
                                    <h5 class="fw-bold mb-1">Drag & Drop files here</h5>
                                    <p class="text-muted small mb-0">or click to browse from your computer</p>
                                    <p class="text-muted small mb-0">(maximum 5 files at once)</p>
                                </div>
                                
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

                            <!-- Preview List -->
                            <div id="filePreviewList" class="mt-4 vstack gap-2"></div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top" id="actionButtons">
                            <button onclick="window.history.back()" class="btn btn-light px-4 py-2 fw-semibold rounded-3 text-muted">
                                Cancel
                            </button>
                            {{-- <a href="{{ route('gallery') }}" class="btn btn-light px-4 py-2 fw-semibold rounded-3 text-muted">Cancel</a> --}}
                            <button class="btn btn-warning px-5 py-2 fw-bold text-dark rounded-3 shadow-sm border-0" type="submit" style="background: #F7BA2C;">
                                <i class="fa-solid fa-upload me-2"></i>Upload Models
                            </button>
                        </div>
                        
                        <!-- Loading Indicator (Hidden by default) -->
                        <div id="loadingIndicator" class="text-center py-4 d-none">
                            <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;"></div>
                            <h5 class="mt-3 fw-bold">Uploading & Processing...</h5>
                            <p class="text-muted small">Please do not close this window.</p>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 1.2rem;
        line-height: 1;
        vertical-align: middle;
    }

    /* Dropzone Styles */
    .border-dashed {
        border: 2px dashed #ced4da;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .drop-zone {
        cursor: pointer;
    }
    .drop-zone:hover, .drop-zone.active {
        border-color: #F7BA2C;
        background-color: rgba(247, 186, 44, 0.05) !important;
    }
    .drop-zone:hover .icon-circle, .drop-zone.active .icon-circle {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }

    /* Preview Item Styles */
    .file-preview-item {
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
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

    // preview renderer (UI UPDATED)
    function renderPreview(files) {
        previewList.innerHTML = '';

        if(files.length > 0) {
            previewList.innerHTML = `<p class="fw-semibold mb-1 small text-muted">Selected Files (${files.length})</p>`;
        }

        Array.from(files).forEach(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            // Pilih icon bedasarkan ekstensi
            const iconClass = ext === 'zip' ? 'fa-file-zipper' : 'fa-cube';
            const badgeColor = ext === 'zip' ? 'bg-danger' : 'bg-primary';

            const item = document.createElement('div');
            item.className = 'file-preview-item d-flex align-items-center p-3 bg-white border rounded-3 shadow-sm';

            item.innerHTML = `
                <div class="bg-light rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fa-solid ${iconClass} fs-4 text-secondary"></i>
                </div>
                <div class="flex-grow-1 text-truncate pe-3">
                    <h6 class="mb-0 fw-bold text-truncate" title="${file.name}">${file.name}</h6>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge ${badgeColor} text-uppercase" style="font-size: 0.65rem;">${ext}</span>
                        <small class="text-muted">${sizeMB} MB</small>
                    </div>
                </div>
                <div class="text-success">
                    <i class="fa-solid fa-circle-check fs-5"></i>
                </div>
            `;

            previewList.appendChild(item);
        });
    }

    // Loading indicator on submit (UI UPDATED)
    const form = document.getElementById('uploadForm');
    const actionButtons = document.getElementById('actionButtons');
    const loadingIndicator = document.getElementById('loadingIndicator');

    form.addEventListener('submit', () => {
        // Sembunyikan tombol
        actionButtons.classList.add('d-none');
        // Sembunyikan dropzone & preview agar fokus ke loading
        dropZone.style.display = 'none';
        previewList.style.display = 'none';
        
        // Tampilkan loading
        loadingIndicator.classList.remove('d-none');
    });    </script>
@endpush
