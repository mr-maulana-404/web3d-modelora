@extends('layouts.admin')

@section('content')
<div class="admin-page">

    <div class="admin-page__header">
        <div>
            <div class="admin-page__eyebrow">Texture Controls</div>
            <h3 class="admin-page__title">Admin Textures</h3>
            <p class="admin-page__subtitle">Library texture bawaan untuk halaman customize user.</p>
        </div>
        <a href="{{ route('admin.textures.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Add Texture
        </a>
    </div>

    <form method="GET" action="{{ route('admin.textures.index') }}" class="admin-toolbar">
        <input type="text" name="search" value="{{ request('search') }}"
            class="form-control form-control-sm" placeholder="Cari texture...">
        <button class="btn btn-warning btn-sm">Search</button>
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Preview</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Path</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($textures as $tex)
                        <tr>
                            <td>{{ $textures->firstItem() + $loop->index }}</td>
                            <td>
                                <img src="{{ asset('storage/' . $tex->texture_path) }}"
                                    width="60" height="60"
                                    style="object-fit:cover; border-radius:8px;">
                            </td>
                            <td>{{ $tex->name }}</td>
                            <td>{{ $tex->category ?? '-' }}</td>
                            <td><code>{{ $tex->texture_path }}</code></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('admin.textures.edit', $tex->id) }}"
                                        class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.textures.destroy', $tex->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus texture ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada texture.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">
            Showing {{ $textures->firstItem() }} to {{ $textures->lastItem() }} of {{ $textures->total() }} entries
        </small>
        <div>{{ $textures->links('pagination::bootstrap-5') }}</div>
    </div>

</div>
@endsection
