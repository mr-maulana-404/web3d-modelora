@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">User Custom Controls</div>
            <h3 class="admin-page__title">Customization Results</h3>
            <p class="admin-page__subtitle">Pantau dan kelola hasil kustomisasi 3D dari user.</p>
        </div>
        </div>

    <form action="{{ route('admin.usercustom.index') }}" method="GET" class="admin-toolbar">
        <input type="text" name="search" value="{{ request('search') }}"
            class="form-control form-control-sm" placeholder="Cari nama kustomisasi, user, atau model asli...">
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
                        <th>Thumbnail</th>
                        <th>Custom Name</th>
                        <th>Original Model</th>
                        <th>User (Owner)</th>
                        <th>Date Created</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customizations as $custom)
                        <tr>
                            <td>{{ $customizations->firstItem() + $loop->index }}</td>
                            <td>
                                @if($custom->thumbnail_path)
                                    <img src="{{ asset('storage/' . $custom->thumbnail_path) }}" width="80" class="rounded border shadow-sm">
                                @else
                                    <span class="badge bg-secondary">No Image</span>
                                @endif
                            </td>
                            <td><strong>{{ $custom->name }}</strong></td>
                            <td>
                                @if($custom->model3d)
                                    {{ $custom->model3d->name }}
                                @else
                                    <span class="text-danger"><i>Model Deleted</i></span>
                                @endif
                            </td>
                            <td>{{ optional($custom->user)->name ?? 'Unknown User' }}</td>
                            <td>{{ $custom->created_at->format('d M Y, H:i') }}</td>
                            <td class="text-center">
                                <form action="{{ route('admin.usercustom.destroy', $custom->id) }}" method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus hasil kustomisasi ini secara permanen?')">
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
                            <td colspan="7" class="text-center py-4 text-muted">
                                Belum ada hasil kustomisasi dari user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
            Showing {{ $customizations->firstItem() ?? 0 }} to {{ $customizations->lastItem() ?? 0 }} of {{ $customizations->total() }} entries
        </small>
        <div>{{ $customizations->withQueryString()->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection