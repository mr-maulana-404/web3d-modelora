@extends('layouts.app')
@section('title', 'Register')

@section('content')
<div class="register-page">

  {{-- Kiri / Komunis --}}
  <div class="left-side">
    <div class="brand-logo">
      {{-- Ganti dengan gambar logo sendiri --}}
      <img src="{{ asset('storage/img/modelora_logo_light.png') }}" alt="MOD3LORA">
      <div class="brand-subtitle">Sign in or Create an account</div>
    </div>

    {{-- footer buat regis ajah --}}
    <footer class="register-footer">
        <ul class="links">
            <li class="copy">Copyright © {{ date('Y') }}</li>
            <li>|</li>
        </ul>
        <ul class="links">
            <li><a href="{{ url('/about') }}">About Us</a></li>
            <li><a href="{{ url('/contact') }}">Contact</a></li>
            <li><a href="{{ url('/help') }}">Help</a></li>
            <li>|</li>
        </ul>
        <span>Follow Us</span>
        <ul class="social">
            <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-x-twitter"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-youtube"></i></a></li>
        </ul>
    </footer>
  </div>

  {{-- Kanan / Fasis --}}
  <div class="right-side">
    <div class="register-card">
      <h2>Sign Up</h2>
      <p>Already have an account? <a href="{{ route('login') }}">Log In</a></p>

      <form action="{{ route('registercheck') }}" method="POST">
        @csrf

        <div>
          <label for="name">Name</label>
          <input type="text" id="name" name="name"
                 class="form-control"
                 placeholder="Please enter your name"
                 value="{{ old('name') }}" required>
        </div>

        <div>
          <label for="email">Email</label>
          <input type="email" id="email" name="email"
                 class="form-control"
                 placeholder="Please enter your email"
                 value="{{ old('email') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="Please enter your password" required>
                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password" style="border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem; max-height: 2.47rem;">
                    <i class="fas fa-eye-slash" style="font-size: 12px"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm your password" required>
                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password_confirmation" style="border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem; max-height: 2.47rem;">
                    <i class="fas fa-eye-slash" style="font-size: 12px"></i>
                </button>
            </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-register">Sign Up</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
  <script>
        // --- Logika Intip Password  ---
        const toggleButtons = document.querySelectorAll('.password-toggle');

        toggleButtons.forEach(toggleButton => {
            toggleButton.addEventListener('click', function (e) {
                // 1. Ambil target ID dari atribut data-target pada tombol yang diklik
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);

                if (passwordInput) {
                    // 2. Ambil elemen ikon di dalam tombol
                    const icon = this.querySelector('i');
                    
                    // Cek tipe input saat ini
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    
                    // Ubah tipe input
                    passwordInput.setAttribute('type', type);
                    
                    // Ubah ikon dari mata tertutup (slash) ke mata terbuka (eye) dan sebaliknya
                    icon.classList.toggle('fa-eye-slash');
                    icon.classList.toggle('fa-eye');
                }
            });
        });
  </script>
@endpush