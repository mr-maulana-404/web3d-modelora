<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Home</div>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link {{ request()->routeIs('admin.models.*') ? '' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseModels" aria-expanded="{{ request()->routeIs('admin.models.*') ? 'true' : 'false' }}" aria-controls="collapseModels">
                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                Model Controls
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse {{ request()->routeIs('admin.models.*') ? 'show' : '' }}" id="collapseModels" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link {{ request()->routeIs('admin.models.*') ? 'active' : '' }}" href="{{ route('admin.models.index') }}">App Model</a>
                    <a class="nav-link {{ request()->routeIs('admin.usercustom.*') ? 'active' : '' }}" href="{{ route('admin.usercustom.index') }}">User Custom</a>
                    <a class="nav-link {{ request()->routeIs('admin.userenhance.*') ? 'active' : '' }}" href="{{ route('admin.userenhance.index') }}">User Enhance</a>
                </nav>
            </div>
            <a class="nav-link {{ request()->routeIs('admin.textures.*') || request()->routeIs('admin.user.textures*') ? '' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTextures" aria-expanded="{{ request()->routeIs('admin.textures.*') || request()->routeIs('admin.user.textures*') ? 'true' : 'false' }}" aria-controls="collapseTextures">
                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                Textures Controls
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse {{ request()->routeIs('admin.textures.*') || request()->routeIs('admin.user.textures*') ? 'show' : '' }}" id="collapseTextures" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link {{ request()->routeIs('admin.textures.*') ? 'active' : '' }}" href="{{ route('admin.textures.index') }}">App Texture</a>
                    <a class="nav-link {{ request()->routeIs('admin.user.textures*') ? 'active' : '' }}" href="{{ route('admin.user.textures') }}">User Texture</a>
                </nav>
            </div>
            <a class="nav-link" href="{{ route('admin.users.index') }}">
                <div class="sb-nav-link-icon"><i class="fas fa-user"></i></div>
                User Management
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        @auth
            <div class="small">Welcome - Admin:</div>
            {{ Str::limit(Auth::user()->name, 27, '...') }}
        @endauth
    </div>
    <div class="sb-sidenav-footer d-sm-none">
        <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn sm-btn btn-danger">Logout</button>
        </form>
    </div>
</nav>
