@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">User Enhance Controls</div>
            <h3 class="admin-page__title">Enhancement Results</h3>
            <p class="admin-page__subtitle">Pantau antrean, status, dan kelola hasil texture enhancement model 3D dari user.</p>
        </div>
    </div>

    <form action="{{ route('admin.userenhance.index') }}" method="GET" class="admin-toolbar">
        <input type="text" name="search" value="{{ request('search') }}"
            class="form-control form-control-sm" placeholder="Cari nama project, user, atau model asli...">
        <button class="btn btn-warning btn-sm" type="submit">
            <i class="fas fa-search"></i>
        </button>
    </form>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-1 mt-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Preview</th>
                        <th>Project Name</th>
                        <th>Mode</th>
                        <th>User (Owner)</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td>{{ $projects->firstItem() + $loop->index }}</td>
                            <td>
                                @if($project->previewImageUrl())
                                    <img src="{{ $project->previewImageUrl() }}" width="80" class="rounded border shadow-sm">
                                @else
                                    <span class="badge bg-secondary">No Preview</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $project->name }}</strong><br>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;" title="{{ $project->uuid }}">
                                    ID: {{ explode('-', $project->uuid)[0] }}...
                                </small>
                            </td>
                            <td>
                                @php
                                    $mode = $project->enhancement_options['mode'] ?? 'Unknown';
                                    $modeColor = $mode === 'meshy_repair_retexture' ? 'text-primary fw-bold' : 'text-muted';
                                @endphp
                                <span class="{{ $modeColor }}">
                                    {{ str_replace('_', ' ', Str::title($mode)) }}
                                </span>
                            </td>
                            <td>{{ optional($project->user)->name ?? 'Unknown User' }}</td>
                            <td>
                                @if($project->status === 'ready')
                                    <span class="badge bg-success">Ready</span>
                                @elseif($project->status === 'processing')
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        <span class="badge bg-primary">Processing ({{ $project->progress ?? 0 }}%)</span>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">{{ Str::title(str_replace('_', ' ', $project->pipeline_stage)) }}</small>
                                @elseif($project->status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-secondary">{{ Str::title(str_replace('_', ' ', $project->status)) }}</span>
                                @endif
                            </td>
                            <td>{{ $project->created_at->format('d M Y, H:i') }}</td>
                            <td class="text-center">
                                <form action="{{ route('admin.userenhance.destroy', $project->id) }}" method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus project ini secara permanen? Seluruh file model, input, dan output di server juga akan terhapus bersih.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Belum ada project enhancement dari user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
            Showing {{ $projects->firstItem() ?? 0 }} to {{ $projects->lastItem() ?? 0 }} of {{ $projects->total() }} entries
        </small>
        <div>{{ $projects->withQueryString()->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection