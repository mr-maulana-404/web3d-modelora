    <aside id="sidebar" class="baseAside">
        <div class="sidebar-content py-4">
            
            {{-- Main Navigation --}}
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link text-white d-flex align-items-center mb-2">
                        <span class="icon-width"><i class="fas fa-home"></i></span>
                        <span class="ms-2 fw-bold">HOME</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/gallery') }}" 
                    class="nav-link {{ request()->routeIs('gallery') ? 'active' : 'text-white' }} d-flex align-items-center mb-2">
                        <span class="icon-width"><i class="fas fa-cube"></i></span>
                        <span class="ms-2 fw-bold">USER MODEL</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('gallery.enhancement.index') }}"
                    class="nav-link {{ request()->routeIs('gallery.enhancement.*') ? 'active' : 'text-white' }} d-flex align-items-center mb-2">
                        <span class="icon-width"><i class="fas fa-wand-magic-sparkles"></i></span>
                        <span class="ms-2 fw-bold">ENHANCEMENT</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('gallery.saved') }}" 
                    class="nav-link {{ request()->routeIs('gallery.saved') ? 'active' : 'text-white' }} d-flex align-items-center mb-2">
                        <span class="icon-width"><i class="fas fa-save"></i></span>
                        <span class="ms-2 fw-bold">SAVED CUSTOMIZATION</span>
                    </a>
                </li>
            </ul>

            <hr class="sidebar-divider mx-3 border-secondary">

            {{-- Top Up Buttons --}}
            <div class="px-3 my-3">
                {{-- Credits Button (Gradient) --}}
                <a href="{{ route('gallery.topup') }}" class="btn btn-credits w-100 mb-3 d-flex align-items-center text-white">
                    <i class="fas fa-coins me-2"></i> CREDITS
                </a>
            </div>

            <hr class="sidebar-divider mx-3 border-secondary">

            {{-- Help Link --}}
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a href="{{ url('/help') }}" class="nav-link text-white d-flex align-items-center">
                        <span class="icon-width"><i class="fas fa-question-circle"></i></span>
                        <span class="ms-2 fw-bold">HELP</span>
                    </a>
                </li>
            </ul>

        </div>
    </aside>
