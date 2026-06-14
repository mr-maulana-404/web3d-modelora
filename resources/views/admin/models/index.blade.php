@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <!-- Header -->
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">Model Controls</div>
            <h3 class="admin-page__title">Model 3D</h3>
            <p class="admin-page__subtitle">Kelola upload, thumbnail, status proses, dan publish model.</p>
        </div>
        <a href="{{ route('admin.models.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Add Model
        </a>
    </div>

    <!-- Search -->
    <form action="{{ route('admin.models.index') }}" method="GET" class="admin-toolbar">
        <input type="text" name="search" value="{{ request('search') }}"
            class="form-control form-control-sm" placeholder="Cari model...">
        <button class="btn btn-warning btn-sm" type="submit">
            <i class="fas fa-search"></i>
        </button>
    </form>

    <!-- Alert -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <p class="alert alert-danger">{{ session('error') }}</p>
    @endif

    <!-- Thumbnail Generator -->
    <div id="thumbnailGenerator" style="display:none;"></div>

    <!-- Table -->
    <div class="card shadow-sm border-1">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Thumbnail</th>
                        <th>Name</th>
                        <th>Path</th>
                        <th>Owner</th>
                        <th>Publish</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($models as $model)
                        <tr id="model-row-{{ $model->id }}" class="{{ $model->processing_status == 'processing' ? 'processing' : '' }}">
                            <td>{{ $models->firstItem() + $loop->index }}</td>
                            <td id="thumbnail-container-{{ $model->id }}">
                                @if($model->thumbnail_path)
                                    <img src="{{ asset('storage/' . $model->thumbnail_path) }}" width="60" class="rounded">
                                @else
                                    <span class="text-muted">No Image</span>
                                @endif
                            </td>
                            <td>{{ $model->name }}</td>
                            <td><code>{{ $model->model_path }}</code></td>
                            <td>{{ optional($model->owner)->name ?? 'Unknown' }}</td>
                            <td>
                                <form action="{{ route('admin.models.toggle', $model) }}" method="POST" style="display:inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-sm 
                                        @if($model->is_published)
                                            btn-success
                                        @else
                                            btn-secondary
                                        @endif
                                        ">
                                        @if($model->is_published)
                                            Yes
                                        @else
                                            No
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td id="status-container-{{ $model->id }}">
                                @if($model->processing_status == 'processing')
                                    <span class="spinner-border spinner-border-sm text-primary"></span>
                                    <span class="status-text">Processing</span>
                                @else
                                    <span class="badge bg-success">Ready</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-between">
                                    <a class="btn btn-sm btn-warning" href="{{ route('admin.models.edit', $model->id) }}">Edit</a>
                                    <form action="{{ route('admin.models.destroy', $model->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus model ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Belum ada model 3D yang diupload.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
            Showing {{ $models->firstItem() }} to {{ $models->lastItem() }} of {{ $models->total() }} entries
        </small>
        <div>{{ $models->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/model-parser.js')

    <script>
        window.addEventListener('DOMContentLoaded', () => {

            const processingRows = document.querySelectorAll("tr.processing");

            processingRows.forEach(row => {

                const modelId = row.id.replace("model-row-", "");
                const modelPath = row.querySelector("code").innerText;
                const modelUrl = "/storage/" + modelPath;

                if (typeof window.processModel === 'function') {
                    window.processModel(modelId, modelUrl);
                }
            });
        });
    </script>
@endpush
