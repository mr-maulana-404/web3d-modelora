@extends('layouts.app')
@section('title', 'Saved Model')

@section('content')
<div class="bodyGallery">
    {{-- NAVBAR SECTION --}}
    <nav class="baseNav">
        {{-- KIRI: Sidebar Color Area (Logo & Toggle) --}}
        <div class="nav-left">
            {{-- Mobile Toggle --}}
            <button class="btn border-0 text-white d-md-none me-2" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="navbar-brand">
                <img src="{{ asset('storage/img/modelora_logo_light.png') }}" alt="MOD3LORA" height="30">
            </a>
        </div>

        {{-- TENGAH: Main Background Area (Search & Filter) --}}
        <div class="nav-right d-flex align-items-center justify-content-between flex-grow-1 ps-4 pe-4">
            
            {{-- Search Bar (Desktop & Tablet) --}}
            <form action="{{ route('gallery') }}" method="GET" class="search-container d-flex align-items-center flex-grow-1 me-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="form-control search-input" placeholder="Search 3D Model">
                <button class="btn btn-search"><i class="fas fa-search"></i></button>

                {{-- biar filter tetap ikut --}}
                <input type="hidden" name="age" value="{{ request('age') }}">
                <input type="hidden" name="gender" value="{{ request('gender') }}">
            </form>

            {{-- Filter Button (Sort By) --}}
            <div class="dropdown d-none d-md-block me-3">
                <button class="btn btn-dark dropdown-toggle filter-btn me-5" type="button" id="dropdownFilter"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    SORT BY
                </button>

                <div class="dropdown-menu dropdown-menu-dark filter-menu p-3" aria-labelledby="dropdownFilter" style="min-width:220px;">
                    
                    {{-- FORM FILTER --}}
                    <form action="{{ route('gallery') }}" method="GET">

                        {{-- Biar search tetap ikut --}}
                        <input type="hidden" name="search" value="{{ request('search') }}">

                        <p class="mb-2 dropdownLabel">AGE</p>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="age" value="kid" id="ageKid"
                                {{ request('age') == 'kid' ? 'checked' : '' }}>
                            <label class="form-check-label" for="ageKid">CHILD</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="age" value="teen" id="ageTeen"
                                {{ request('age') == 'teen' ? 'checked' : '' }}>
                            <label class="form-check-label" for="ageTeen">TEEN</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="age" value="adult" id="ageAdult"
                                {{ request('age') == 'adult' ? 'checked' : '' }}>
                            <label class="form-check-label" for="ageAdult">ADULT</label>
                        </div>

                        <hr class="dropdown-divider border-secondary my-2">

                        <p class="mb-2 dropdownLabel">GENDER</p>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" value="male" id="genderMale"
                                {{ request('gender') == 'male' ? 'checked' : '' }}>
                            <label class="form-check-label" for="genderMale">MALE</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" value="female" id="genderFemale"
                                {{ request('gender') == 'female' ? 'checked' : '' }}>
                            <label class="form-check-label" for="genderFemale">FEMALE</label>
                        </div>

                        <hr class="dropdown-divider border-secondary my-2">

                        <div class="d-flex justify-content-center gap-2">
                            <button type="submit" class="btn btn-warning btn-sm apply-btn">
                                Apply
                            </button>

                            <a href="{{ route('gallery') }}" class="btn btn-secondary btn-sm reset-btn">
                                Reset
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            @guest
                {{-- GUEST STATE: SWITCH BUTTON --}}
                <div class="auth-buttons">
                    <a href="{{ route('login') }}" class="btn-login">LOG IN</a>
                    <a href="{{ route('register') }}" class="btn-signup">SIGN UP</a>
                </div>
            @endguest

            @auth
                {{-- AUTH STATE: MESSAGE & USER DROPDOWN --}}
                
                {{-- Icon Pesan (Desktop Only) --}}
                <a href="#" class="nav-icon-btn d-none d-lg-block me-3">
                    <i class="fas fa-envelope"></i>
                </a>

                {{-- User Profile --}}
                <div class="dropdown user-dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle-custom" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="ms-1 user-avatar-icon-nav">
                            <i class="fas fa-user"></i>
                        </div>
                        {{-- Tampilan Mobile: Nama user langsung di samping icon --}}
                        <div class="user-info-mobile d-lg-none ms-3">
                            <span class="d-block fw-bold text-white">{{ Auth::user()->name }}</span>
                            <span class="d-block text-white-50 small">{{ Auth::user()->email }}</span>
                        </div>
                    </a>

                    {{-- Dropdown Menu --}}
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu" aria-labelledby="userDropdown">
                        {{-- Header di dalam dropdown (Desktop Style) --}}
                        <li class="px-3 py-2 d-none d-lg-block">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar-icon sm">
                                    <i class="fas fa-user"></i>
                                </div>
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

                {{-- Mobile Only: Menu User (dikeluarkan dari dropdown standard bootstrap untuk styling flat di mobile) --}}
                <div class="mobile-user-menu d-lg-none mt-3">
                    <a class="mobile-link" href="#"><i class="fas fa-envelope me-3"></i>MESSAGE</a>
                    @if (Auth::user()->usertype === 'admin')
                         <a class="mobile-link" href="{{ route('admin.dashboard') }}">DASHBOARD ADMIN</a>
                    @endif
                    <a class="mobile-link" href="{{ route('home') }}">CHANGE PASSWORD</a>
                    <form action="{{ route('logout') }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="mobile-link text-danger bg-transparent border-0 p-0 fw-bold">LOG OUT</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>   

    {{-- SIDEBAR SECTION --}}
    <x-gallery-sidebar />

    {{-- MAIN CONTENT SECTION --}}
    <main class="baseMain p-4">
        
        <div class="container py-4">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                @forelse($models as $model)
                    @php
                        $model3d = $model->model3d;
                        $downloadPayload = [
                            'id' => $model->id,
                            'name' => $model->name,
                            'obj_download_url' => route('customizations.download.obj', $model),
                            'model_name' => $model3d?->name,
                            'model_path' => $model3d?->model_path,
                            'model_format' => $model3d?->model_format,
                            'parts' => $model3d?->parts?->map(fn ($part) => [
                                'id' => $part->id,
                                'part_name' => $part->part_name,
                                'mesh_name' => $part->mesh_name,
                            ])->values() ?? [],
                            'textures' => $model->textures->map(fn ($texture) => [
                                'model_part_id' => $texture->model_part_id,
                                'mesh_name' => $texture->part?->mesh_name,
                                'part_name' => $texture->part?->part_name,
                                'texture_type' => $texture->texture_type,
                                'texture_path' => $texture->texture_path,
                                'color_value' => $texture->color_value,
                            ])->values(),
                        ];
                    @endphp
                    <div class="col">
                        <div class="card saved-model-card h-100 border-0 shadow-sm bg-white">
                            
                            {{-- Thumbnail Wrapper --}}
                            <div class="ratio ratio-4x3 card-img-wrapper">
                                <img src="{{ asset('storage/' . $model->thumbnail_path) }}" 
                                    class="card-img-top object-fit-cover" 
                                    alt="{{ $model->name }}">
                            </div>

                            {{-- Card Body --}}
                            {{-- Perbaikan 1: p-4 diubah menjadi p-3 untuk memberikan ruang horizontal yang lebih luas --}}
                            <div class="card-body fw-bold d-flex flex-column p-3">
                                <p class="card-title text-dark mb-3">
                                    {{ $model->name }}
                                </p>
                                
                                {{-- Buttons Area --}}
                                <div class="mt-auto d-flex gap-1 align-items-center w-100">
                                    
                                    {{-- Button Edit --}}
                                    <a href="{{ route('gallery.saved.edit', $model->id) }}" 
                                       class="btn btn-action custom-btn-edit flex-grow-1 text-decoration-none">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>

                                    {{-- Button Download --}}
                                    <button type="button"
                                            class="btn btn-action custom-btn-download flex-grow-1 btn-download-customized"
                                            data-download-customization='@json($downloadPayload)'>
                                        <i class="fas fa-download me-1"></i> Download
                                    </button>

                                    {{-- Button Delete --}}
                                    {{-- Perbaikan 2: Hapus px-3 dan biarkan SCSS mengatur tombol Delete jadi bentuk kotak --}}
                                    <button type="button" 
                                            class="btn btn-action custom-btn-delete btn-delete-model"
                                            data-id="{{ $model->id }}" 
                                            data-name="{{ $model->name }}">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    {{-- Hidden Form Delete --}}
                                    <form id="delete-form-{{ $model->id }}" 
                                          action="{{ route('gallery.saved.delete', $model->id) }}" 
                                          method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="container d-flex justify-content-center py-5 w-100">
                        <div class="text-muted d-flex flex-column justify-content-center align-items-center gap-2">
                            <i class="fas fa-folder-open fa-3x mb-2 opacity-25"></i>
                            <p>There are no saved models yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

    </main>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/pages/appsavedmodel.js',
        'resources/js/pages/downloadcustomized.js'
    ])
@endpush
