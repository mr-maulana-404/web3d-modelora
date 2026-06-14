@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="login-page">
    <div class="brand-logo">
        <img src="{{ asset('storage/img/modelora_logo_light.png') }}" alt="MOD3LORA">
        <div class="brand-subtitle">
            Sign in or Create an account
        </div>
    </div>

    <div class="login-card">
        <h2>Log In</h2>
        <p>
            please enter your email and password <br>
            New User? <a href="{{ route('register') }}">Create an Account</a>
        </p>

        @if ($errors->any())
            <div class="alert alert-danger fade show alert-custom" role="alert">
                <strong>Oops!</strong> {{ $errors->first('email') }}
            </div>
        @endif

        <form action="{{ route('logincheck') }}" method="POST">
            @csrf
            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" name="email" id="email" class="form-control" placeholder="Masukkan email anda" value="{{ old('email') }}">
            </div>

            <!-- Password -->
            <div class="mb-2">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem; max-height: 2.47rem;">
                        <i class="fas fa-eye-slash" style="font-size: 12px"></i>
                    </button>
                </div>
            </div>

            <div class="forgot d-flex justify-content-between align-items-center mb-3">
                {{-- <a href="{{ route('password.request') }}">Forgot your password?</a> --}}
                <a href="#">Forgot your password?</a>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-auth">Log In</button>
            </div>
        </form>
    </div>

    {{-- Ini Futer --}}
    <x-footer />
</div>
@endsection

@push('scripts')
    <script>
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');

        if (toggleButton && passwordInput) {
            toggleButton.addEventListener('click', function (e) {
                // Ambil elemen ikon di dalam tombol
                const icon = this.querySelector('i');
                
                // Cek tipe input saat ini
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                
                // Ubah tipe input
                passwordInput.setAttribute('type', type);
                
                // Ubah ikon dari mata tertutup (slash) ke mata terbuka (eye) dan sebaliknya
                icon.classList.toggle('fa-eye-slash');
                icon.classList.toggle('fa-eye');
            });
        }
    </script>
@endpush
