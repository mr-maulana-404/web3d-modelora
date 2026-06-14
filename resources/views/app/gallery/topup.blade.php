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

        {{--  --}}
        <div class="nav-right d-flex align-items-center justify-content-between flex-grow-1 ps-4 pe-4">

            {{-- Credit saat ini --}}
            <div class="d-flex align-items-center me-auto credit-display d-none d-md-flex">
                <i class="fas fa-coins me-2"></i>
                <span class="fw-bold">
                    {{ number_format($credits) }} Credits
                </span>
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
            <div class="row g-4 justify-content-center">

                @php
                    // Menambahkan parameter old_price dan discount sesuai desain
                    $packages = [
                        ['credits' => 100, 'price' => 10000, 'old_price' => null, 'discount' => null],
                        ['credits' => 300, 'price' => 25000, 'old_price' => 30000, 'discount' => 5000],
                        ['credits' => 700, 'price' => 50000, 'old_price' => 70000, 'discount' => 20000],
                        ['credits' => 1500, 'price' => 100000, 'old_price' => 150000, 'discount' => 50000],
                    ];
                @endphp

                @foreach($packages as $pkg)
                <div class="col-12 col-sm-6 col-md-3">
                    {{-- Seluruh card kita jadikan tombol pemicu JS (.btnTopup) --}}
                    <div class="card border-0 shadow-sm topup-card btnTopup"
                         data-credits="{{ $pkg['credits'] }}"
                         data-price="{{ $pkg['price'] }}">

                        {{-- Bagian Atas: Background Gradient, Gambar, & Text Outline Besar --}}
                        <div class="card-img-top-custom">
                            {{-- Ganti src ini dengan gambar koin yang kamu generate --}}
                            <img src="{{ asset('storage/img/credit.png') }}" alt="coins" class="coin-img">
                            <h1 class="credit-large-text">{{ $pkg['credits'] }}</h1>
                        </div>

                        {{-- Bagian Bawah: Informasi Harga --}}
                        <div class="card-body bg-white text-start px-4 py-3">
                            <p class="mb-1 text-dark fw-bold" style="font-size: 11px;">{{ $pkg['credits'] }} Credits</p>
                            
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h5 class="fw-bolder mb-0 topup-price">Rp{{ number_format($pkg['price'], 0, ',', '.') }}</h5>
                                    
                                    @if($pkg['old_price'])
                                        <small class="text-muted text-decoration-line-through old-price">
                                            Rp{{ number_format($pkg['old_price'], 0, ',', '.') }}
                                        </small>
                                    @else
                                        {{-- Spacer transparan jika tidak ada harga coret agar tinggi card tetap sama --}}
                                        <small class="old-price d-block opacity-0">Rp0</small>
                                    @endif
                                </div>
                                
                                @if($pkg['discount'])
                                    <span class="badge badge-discount mb-1">- Rp{{ number_format($pkg['discount'], 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
                @endforeach

            </div>
        </div>

    </main>

</div>
@endsection

@push('scripts')
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="{{ config('midtrans.client_key') }}"></script>

    <script>
    document.querySelectorAll('.btnTopup').forEach(btn => {
        btn.addEventListener('click', function () {

            let price = this.dataset.price;
            let credits = this.dataset.credits;

            fetch("{{ route('topup.transaction') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    price: price,
                    credits: credits
                })
            })
            .then(res => res.json())
            .then(data => {

                window.snap.pay(data.snap_token, {
                    onSuccess: function(result){

                        fetch("{{ url('/topup/success') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                credits: credits
                            })
                        })
                        .then(() => {
                            alert("Top up berhasil!");
                            location.reload();
                        });

                    },
                    onPending: function(result){
                        alert("Waiting payment...");
                    },
                    onError: function(result){
                        alert("Payment failed!");
                    }
                });

            });

        });
    });
    </script>

    {{-- @vite('resources/js/pages/appgallery.js') --}}
    @vite('resources/js/pages/appsavedmodel.js')
@endpush