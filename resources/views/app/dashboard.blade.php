@extends('layouts.app')
@section('title', 'Homepage')

@section('content') 
    {{-- Navbar --}}
    <x-dashboard-navbar />

    <main class="main-body">
        {{-- Hero Section --}}
        <section class="hero">
            <div class="container hero-container">
                <h1>
                    Discover, Customize,<br>
                    and Share <span>3D Models</span>
                </h1>
                <p>
                    A modern platform to explore interactive 3D assets,
                    customize designs, and publish your own creations.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('gallery.create') }}" class="btn-primary">
                        Upload Model
                    </a>
                    <a href="#modelSection" class="btn-outline">
                        Explore Models
                    </a>
                </div>
            </div>
        </section>

        {{-- Model --}}
        <section id="modelSection" class="models-section">
            <div class="container">
                <div class="section-header">
                    <h2>Explore 3D Models</h2>

                    <div class="search-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        
                        {{-- Tambahkan ID untuk trigger search JS --}}
                        <input type="text" id="searchModelInput" placeholder="Search 3D models...">
                    </div>
                </div>
                
                {{-- Tambahkan ID modelsGridContainer untuk target update JS --}}
                <div class="models-grid" id="modelsGridContainer">
                    
                    @forelse ($models as $model)
                    <div class="model-card" 
                        style="cursor: pointer;"
                        data-bs-toggle="modal" 
                        data-bs-target="#modelDetailModal"
                        data-model-json="{{ json_encode($model) }}">
                        
                        <div class="model-preview">
                            <img src="{{ $model->thumbnail_path ? asset('storage/' . $model->thumbnail_path) : 'https://dummyimage.com/500x300/ccc/fff&text=No+Thumbnail' }}" alt="{{ $model->name }}">
                        </div>
                        <div class="model-info">
                            <h4>{{ $model->name }}</h4>
                            <span class="creator">by <strong>{{ $model->owner->name ?? 'Unknown' }}</strong></span>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <p style="text-align: center; color: #666;">No models published yet.</p>
                    </div>
                    @endforelse

                </div>
            </div>
        </section>
    </main>
    
    {{-- Footer --}}
    <x-big-footer />

    {{-- Modal --}}
    <div class="modal fade" id="modelDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalModelName">Model Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div id="viewerContainer" style="width: 100%; height: 500px; background: #2b2b2b; border-radius: 8px; overflow: hidden;"></div>
                        </div>
                        
                        <div class="col-md-4 mt-3 mt-md-0">
                            <h6 class="text-muted mb-1">Category</h6>
                            <p id="modalModelCategory" class="fw-bold"></p>

                            <h6 class="text-muted mb-1 mt-3">Description</h6>
                            <p id="modalModelDescription"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.currentUserId = {{ Auth::id() ?? 'null' }};
    </script>
    @vite('resources/js/pages/userdashboard.js')
    @vite('resources/js/pages/appgallery.js')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchModelInput');
        const modelsGrid = document.getElementById('modelsGridContainer');
        let typingTimer; // Timer untuk debounce

        searchInput.addEventListener('input', function() {
            clearTimeout(typingTimer); // Reset timer setiap kali user mengetik
            
            // Mulai timer baru (300ms setelah user berhenti mengetik)
            typingTimer = setTimeout(() => {
                const query = this.value;

                // Panggil API dashboard dengan format JSON
                fetch(`{{ route('home') }}?search=${query}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest', // Menandakan ini adalah AJAX Request
                        'Accept': 'application/json'          // Kita minta balasan berupa JSON
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Kosongkan grid yang lama
                    modelsGrid.innerHTML = '';

                    // Jika data kosong
                    if(data.length === 0) {
                        modelsGrid.innerHTML = '<div class="col-12"><p style="text-align: center; color: #666;">No models found matching your search.</p></div>';
                        return;
                    }

                    // Looping data baru hasil pencarian dan render HTML-nya
                    data.forEach(model => {
                        const thumbnail = model.thumbnail_path 
                            ? `{{ asset('storage') }}/${model.thumbnail_path}` 
                            : 'https://dummyimage.com/500x300/ccc/fff&text=No+Thumbnail';
                            
                        // Pastikan owner tidak null
                        const ownerName = model.owner ? model.owner.name : 'Unknown';

                        // Escape quotes agar format JSON tidak rusak di dalam atribut HTML
                        const modelJsonString = JSON.stringify(model).replace(/"/g, '&quot;');

                        const cardHtml = `
                            <div class="model-card" 
                                style="cursor: pointer;"
                                data-bs-toggle="modal" 
                                data-bs-target="#modelDetailModal"
                                data-model-json="${modelJsonString}">
                                
                                <div class="model-preview">
                                    <img src="${thumbnail}" alt="${model.name}">
                                </div>
                                <div class="model-info">
                                    <h4>${model.name}</h4>
                                    <span class="creator">by <strong>${ownerName}</strong></span>
                                </div>
                            </div>
                        `;
                        
                        // Masukkan ke dalam grid
                        modelsGrid.insertAdjacentHTML('beforeend', cardHtml);
                    });
                })
                .catch(error => console.error('Error searching models:', error));
                
            }, 300); // Waktu tunda 300 milidetik
        });
    });
    </script>
@endpush