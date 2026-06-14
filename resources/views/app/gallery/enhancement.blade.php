@extends('layouts.app')
@section('title', 'Model Enhancement')

@php
    use Illuminate\Support\Str;

    $modeLabels = [
        'local_texture' => 'Local Texture Enhance',
        'meshy_repair_retexture' => 'Meshy Repair + Retexture',
    ];
@endphp

@section('content')
<div class="bodyGallery">
    <nav class="baseNav">
        <div class="nav-left">
            <button class="btn border-0 text-white d-md-none me-2" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('home') }}" class="navbar-brand">
                <img src="{{ asset('storage/img/modelora_logo_light.png') }}" alt="MOD3LORA" height="30">
            </a>
        </div>

        <div class="nav-right d-flex align-items-center justify-content-end flex-grow-1 ps-4 pe-4">
            @auth
                <div class="dropdown user-dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle-custom" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="ms-1 user-avatar-icon-nav">
                            <i class="fas fa-user"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu" aria-labelledby="userDropdown">
                        <li class="px-3 py-2 d-none d-lg-block">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar-icon sm"><i class="fas fa-user"></i></div>
                                <div class="ms-3">
                                    <span class="d-block fw-bold text-white">{{ Auth::user()->name }}</span>
                                    <span class="d-block text-white-50 small" style="font-size: 0.75rem;">{{ Auth::user()->email }}</span>
                                </div>
                            </div>
                        </li>
                        @if (Auth::user()->usertype === 'admin')
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard Admin
                                </a>
                            </li>
                        @endif
                        <li><a class="dropdown-item fw-bold mt-2" href="{{ route('home') }}">CHANGE PASSWORD</a></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger fw-bold mt-1">LOG OUT</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </nav>

    <x-gallery-sidebar />

    <main class="baseMain p-4 target-enhancement-dashboard">
        <div class="dashboard-header mb-4 pb-2">
            <h2 class="fw-bold mb-1 header-title">Model Enhancement</h2>
            <p class="text-muted subtitle-text">Transform and upscale your 3D assets. Upload packages, select pipelines, and optimize outputs seamlessly.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-custom alert-success d-flex align-items-center rounded-4 mb-4 shadow-sm">
                <i class="fas fa-check-circle me-3 fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-custom alert-danger d-flex align-items-center rounded-4 mb-4 shadow-sm">
                <i class="fas fa-exclamation-circle me-3 fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-custom alert-danger rounded-4 mb-4 shadow-sm">
                <div class="d-flex align-items-center mb-2 fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i> Please correct the following errors:
                </div>
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card main-form-card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <form action="{{ route('gallery.enhancement.store') }}" method="POST" enctype="multipart/form-data" id="enhancementUploadForm">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase tracking-wider small text-secondary mb-3">Select Optimization Mode</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="radio" class="btn-check" name="mode" id="modeLocal" value="local_texture" @checked(old('mode', 'local_texture') === 'local_texture') autocomplete="off" required>
                                <label class="btn mode-selector-card w-100 h-100 p-3 text-start d-flex align-items-start" for="modeLocal">
                                    <div class="mode-icon-wrapper text-primary me-3 mt-1">
                                        <i class="fas fa-images fa-lg"></i>
                                    </div>
                                    <div>
                                        <div class="mode-title fw-bold">Texture Enhance</div>
                                        <div class="mode-desc text-muted small mt-1">Refine and sharpen embedded model textures locally without mesh alteration.</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <input type="radio" class="btn-check" name="mode" id="modeMeshy" value="meshy_repair_retexture" @checked(old('mode') === 'meshy_repair_retexture') autocomplete="off" required>
                                <label class="btn mode-selector-card w-100 h-100 p-3 text-start d-flex align-items-start" for="modeMeshy">
                                    <div class="mode-icon-wrapper text-warning me-3 mt-1">
                                        <i class="fas fa-magic fa-lg"></i>
                                    </div>
                                    <div>
                                        <div class="mode-title fw-bold">Repair + Retexture (Experimental)</div>
                                        <div class="mode-desc text-muted small mt-1">Automated mesh topology correction paired with complete AI texturing.</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 align-items-start">
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Model Asset Package</label>
                            <div class="file-input-wrapper position-relative">
                                <input type="file" name="model_file" id="enhancementModelFile" class="form-control custom-file-input" accept=".glb,.gltf,.zip" required>
                            </div>
                            <div class="form-text text-muted mt-2 fs-7">Supports <strong>.glb</strong>, <strong>.gltf</strong>, or packed <strong>.zip</strong> archives. Max file weight limit: 100 MB.</div>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Asset Custom Identifier</label>
                            <input type="text" name="name" class="form-control custom-text-input" value="{{ old('name') }}" maxlength="255" placeholder="Automatically extracts from filename">
                        </div>

                        <div class="col-lg-4" id="promptContainer">
                            <label class="form-label fw-semibold">Text Generation Prompt</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-pen-nib"></i></span>
                                <input type="text" name="text_style_prompt" class="form-control custom-text-input border-start-0 ps-1" value="{{ old('text_style_prompt') }}" maxlength="600" placeholder="e.g., hyper-realistic medieval iron armor, 8k...">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="description" value="{{ old('description') }}">
                    <div id="enhancementFilePreview" class="d-none alert alert-preview-card border rounded-4 mt-4 mb-0 p-3 d-flex align-items-center"></div>

                    <div class="d-flex justify-content-end border-top mt-4 pt-3">
                        <button type="submit" id="runButton" class="btn btn-warning fw-bold px-4 py-2 rounded-3 shadow-sm text-uppercase tracking-wide d-inline-flex align-items-center transition-all">
                            <i class="fas fa-play me-2 small"></i> Run 
                            <span id="creditCost" class="ms-2 fw-bolder text-danger bg-danger-subtle px-2 py-0.5 rounded small border border-danger-subtle">
                                -20 credits
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card data-table-card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold m-0 text-dark">Job Pipelines Status</h5>
                <span class="badge bg-light text-muted border px-2 py-1.5 rounded-3 fw-medium">Real-time Updates Active</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 custom-dashboard-table">
                        <thead>
                            <tr>
                                <th class="ps-4 text-secondary text-uppercase tracking-wider fs-7 fw-bold">Job Target Asset</th>
                                <th class="text-secondary text-uppercase tracking-wider fs-7 fw-bold">Enhance Pipeline</th>
                                <th class="text-secondary text-uppercase tracking-wider fs-7 fw-bold">Processing Status</th>
                                <th class="text-secondary text-uppercase tracking-wider fs-7 fw-bold" style="min-width: 240px;">Pipeline Progress</th>
                                <th class="text-secondary text-uppercase tracking-wider fs-7 fw-bold">Generated Artifact</th>
                                <th class="text-end pe-4 text-secondary text-uppercase tracking-wider fs-7 fw-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody id="enhancementJobTable">
                            @forelse($projects as $project)
                                @php
                                    $mode = data_get($project->enhancement_options, 'mode', 'local_texture');
                                @endphp
                                <tr data-project-id="{{ $project->id }}" data-poll-url="{{ route('gallery.enhancement.poll', $project) }}" class="table-row-hover">
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="job-avatar-box bg-light text-secondary rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="fas fa-cube fa-lg text-secondary opacity-75"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark job-name">{{ $project->name }}</div>
                                                <div class="text-muted job-input fs-7 text-truncate-custom mt-0.5" title="{{ $project->input_glb_path }}">{{ $project->input_glb_path ?: 'No input package path' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge custom-badge bg-light-subtle text-dark border-light-subtle">
                                            <i class="fas fa-code-branch me-1 text-muted small"></i>{{ $modeLabels[$mode] ?? Str::headline($mode) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-badge px-2.5 py-1.5 rounded-3 text-uppercase fs-8 fw-bold {{ $project->status === 'ready' ? 'text-bg-success' : ($project->status === 'failed' ? 'text-bg-danger' : ($project->status === 'processing' ? 'text-bg-warning' : 'text-bg-secondary')) }}">
                                            {{ Str::headline($project->status ?? 'awaiting_upload') }}
                                        </span>
                                        <div class="text-muted mt-1 stage-text font-monospace fs-8">Stage: {{ Str::headline($project->pipeline_stage ?? 'not started') }}</div>
                                    </td>
                                    <td>
                                        <div class="progress-container-wrapper">
                                            <div class="progress rounded-pill mb-1" style="height: 8px;" role="progressbar" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar rounded-pill {{ $project->status === 'processing' ? 'progress-bar-striped progress-bar-animated' : '' }}"
                                                    style="width: {{ $project->progress }}%;">
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="progress-text font-monospace fs-7 fw-bold text-dark">{{ $project->progress }}% completed</span>
                                                <span class="small text-danger error-text text-truncate-custom fs-8" title="{{ $project->error_message }}">{{ $project->error_message ?: '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="preview-wrapper position-relative {{ $project->previewImageUrl() ? '' : 'd-none' }}">
                                                <img class="preview-image rounded-3 border img-thumbnail p-0 shadow-xs" src="{{ $project->previewImageUrl() ?: '' }}" alt="Preview" style="width: 48px; height: 48px; object-fit: cover;">
                                            </div>
                                            <div class="text-muted output-path font-monospace fs-8 text-truncate-custom" style="max-width: 140px;" title="{{ $project->output_glb_path }}">
                                                {{ $project->output_glb_path ? basename($project->output_glb_path) : 'Awaiting artifact...' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-flex flex-column align-items-center gap-1 text-end pe-4">
                                        <a href="{{ $project->outputGlbUrl() ?: '#' }}"
                                            class="btn btn-sm btn-action-download py-1.5 px-3 rounded-3 d-inline-flex align-items-center fw-semibold text-decoration-none shadow-xs {{ $project->outputGlbUrl() ? 'btn-primary' : 'btn-outline-secondary disabled' }}"
                                            download>
                                            <i class="fas fa-cloud-download-alt me-1.5"></i> Download
                                        </a>
                                        <form action="{{ route('gallery.enhancement.delete', $project) }}" method="POST" class="mt-1.5" onsubmit="return confirm('Are you sure you want to delete this enhancement project?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2.5 rounded-3 d-inline-flex align-items-center fw-semibold shadow-xs">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr id="emptyEnhancementRow">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="py-4">
                                            <i class="fas fa-folder-open fa-3x text-muted opacity-50 mb-3"></i>
                                            <p class="mb-0 fw-medium">No processing execution jobs discovered in your pipeline profile.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">
                Showing {{ $projects->firstItem() }} to {{ $projects->lastItem() }} of {{ $projects->total() }} entries
            </small>
            <div>{{ $projects->links('pagination::bootstrap-5') }}</div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // CONDITIONAL FORM LOGIC: RADIO TOGGLES
    const modeRadios = document.querySelectorAll('input[name="mode"]');
    const promptContainer = document.getElementById('promptContainer');
    const creditCost = document.getElementById('creditCost');

    function toggleFormPipelineFields() {
        const activeRadio = document.querySelector('input[name="mode"]:checked');
        if (!activeRadio) return;

        if (activeRadio.value === 'meshy_repair_retexture') {
            // Tampilkan text box prompt, tampilkan teks (-20 credits)
            promptContainer.style.display = 'block';
            creditCost.classList.remove('d-none');
        } else {
            // Sembunyikan text box prompt, sembunyikan teks (-20 credits)
            promptContainer.style.display = 'none';
            creditCost.classList.add('d-none');
        }
    }

    modeRadios.forEach(radio => radio.addEventListener('change', toggleFormPipelineFields));
    toggleFormPipelineFields(); // Jalankan initial state saat pertama kali load halaman

    // FILE ATTACHMENT PREVIEW DYNAMICS
    const input = document.getElementById('enhancementModelFile');
    const preview = document.getElementById('enhancementFilePreview');

    input?.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            preview.classList.add('d-none');
            preview.innerHTML = '';
            return;
        }

        const ext = file.name.split('.').pop().toLowerCase();
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        preview.innerHTML = `
            <div class="p-1 d-flex align-items-center gap-3 w-100">
                <div class="bg-warning-subtle text-warning border border-warning-subtle rounded-3 p-2.5 d-flex align-items-center justify-content-center">
                    <i class="fas fa-file-archive fa-lg"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark fs-6">${file.name}</div>
                    <div class="small text-muted mt-0.5">Format Extension: <span class="badge bg-secondary-subtle text-secondary border uppercase font-monospace">${ext.toUpperCase()}</span> | Computational Package Size: <span class="fw-bold text-dark">${sizeMb} MB</span></div>
                </div>
            </div>
        `;
        preview.classList.remove('d-none');
    });

    const headline = (value) => (value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());

    const syncRow = (row, payload) => {
        row.querySelector('.status-badge').textContent = headline(payload.status || 'awaiting_upload');
        row.querySelector('.stage-text').textContent = `Stage: ${headline(payload.pipeline_stage || 'not started')}`;

        const badge = row.querySelector('.status-badge');
        badge.className = 'badge status-badge px-2.5 py-1.5 rounded-3 text-uppercase fs-8 fw-bold';
        if (payload.status === 'ready') badge.classList.add('text-bg-success');
        else if (payload.status === 'failed') badge.classList.add('text-bg-danger');
        else if (payload.status === 'processing') badge.classList.add('text-bg-warning');
        else badge.classList.add('text-bg-secondary');

        const progress = Number(payload.progress || 0);
        const progressBar = row.querySelector('.progress-bar');
        progressBar.style.width = `${progress}%`;
        progressBar.classList.toggle('progress-bar-striped', payload.status === 'processing');
        progressBar.classList.toggle('progress-bar-animated', payload.status === 'processing');
        row.querySelector('.progress-text').textContent = `${progress}% completed`;
        row.querySelector('.error-text').textContent = payload.error_message || '';
        
        // Output path file formatting
        const outputNode = row.querySelector('.output-path');
        if (payload.output_glb_path) {
            outputNode.textContent = payload.output_glb_path.split('/').pop();
            outputNode.title = payload.output_glb_path;
        } else {
            outputNode.textContent = 'Awaiting artifact...';
        }

        const link = row.querySelector('.btn-action-download');
        if (payload.output_glb_url) {
            link.href = payload.output_glb_url;
            link.className = 'btn btn-sm btn-action-download py-1.5 px-3 rounded-3 d-inline-flex align-items-center fw-semibold text-decoration-none shadow-xs btn-primary';
        } else {
            link.href = '#';
            link.className = 'btn btn-sm btn-action-download py-1.5 px-3 rounded-3 d-inline-flex align-items-center fw-semibold text-decoration-none shadow-xs btn-outline-secondary disabled';
        }

        const previewWrapper = row.querySelector('.preview-wrapper');
        const previewImage = row.querySelector('.preview-image');

        if (payload.preview_image_url) {
            previewImage.src = payload.preview_image_url;
            previewWrapper.classList.remove('d-none');
        } else {
            previewWrapper.classList.add('d-none');
        }
    };

    const pollRows = () => {
        document.querySelectorAll('tr[data-poll-url]').forEach(async (row) => {
            const badgeText = row.querySelector('.status-badge')?.textContent?.trim()?.toLowerCase();
            if (badgeText === 'ready' || badgeText === 'failed') return;

            try {
                const response = await fetch(row.dataset.pollUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) return;

                syncRow(row, await response.json());
            } catch (error) {
                console.error('Enhancement polling failed', error);
            }
        });
    };

    pollRows();
    setInterval(pollRows, 5000);
});
</script>
@endpush