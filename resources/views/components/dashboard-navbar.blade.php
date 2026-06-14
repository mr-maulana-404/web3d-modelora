<nav class="dashboard-navbar navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4 px-lg-5">
        
        {{-- 1. LOGO SECTION --}}
        <a class="navbar-brand" href="{{ route('home') }}">
            {{-- Logo (Warna Gelap) --}}
            <img src="{{ asset('storage/img/modelora_logo_dark.png') }}" alt="MOD3LORA" class="nav-logo logo-scrolled">
            
            {{-- Logo (Warna terang) --}}
            <img src="{{ asset('storage/img/modelora_logo_light.png') }}" alt="MOD3LORA" class="nav-logo logo-default">
        </a>

        {{-- 2. HAMBURGER BUTTON (MOBILE ONLY) --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="toggler-icon"><i class="fas fa-bars"></i></span>
        </button>

        {{-- 3. CONTENT SECTION (NAVLINKS & AUTH) --}}
        <div class="collapse navbar-collapse" id="navbarContent">
            
            {{-- A. NAV LINKS (CENTER-LEFT) --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('home') }}">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://multimediaimaging.id/about-us" target="_blank">ABOUT US</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('help*') ? 'active' : '' }}" href="{{ url('/help') }}">HELP</a>
                </li>
                {{-- SPECIAL LINK: MODEL GALLERY --}}
                <li class="nav-item">
                    <a class="nav-link nav-btn-gallery {{ request()->is('gallery*') ? 'active' : '' }}" href="{{ url('/gallery') }}">MODELS</a>
                </li>
            </ul>

            {{-- B. AUTH SECTION (RIGHT SIDE) --}}
            <div class="d-flex align-items-center auth-container">
                
                {{-- Divider untuk Mobile --}}
                <div class="mobile-divider d-lg-none my-3"></div>

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
                            <div class="ms-3 user-avatar-icon">
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
        </div>
    </div>
</nav>

