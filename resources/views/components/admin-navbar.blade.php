<nav class="sb-topnav navbar navbar-expand navbar-dark">
    <!-- Sidebar Toggle-->
    <button class="btn btn-link order-1 order-lg-0 me-3 me-lg-2" id="sidebarToggle" type="button" aria-label="Toggle admin navigation"><i class="fas fa-bars"></i></button>
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="{{ route('admin.dashboard') }}">
        <img src="{{ asset('storage/img/navbrand_modelora.png') }}" alt="MODELORA" width="140">
    </a>
    <!-- Navbar-->
    @auth
        <div class="dropdown ms-auto me-3 me-lg-4">
            <button class="btn dropdown-toggle d-flex align-items-center admin-dropdown-custom" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle me-2"></i> {{ Str::limit(Auth::user()->name, 6, '...') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if (Auth::user()->usertype === 'admin')
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('home') }}">
                            <i class="fas fa-home"></i> Dashboard User
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('home') }}">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </li>
                @else
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('home') }}">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item d-flex align-items-center">
                            <i class="fas fa-door-open"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    @endauth
</nav>
