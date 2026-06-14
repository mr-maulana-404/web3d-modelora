<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\System\CreditController;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    // Menampilkan halaman login
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin();
        }
        return view('auth.login');
    }

    // Menampilkan halaman pendaftaran
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin();
        }
        return view('auth.register');
    }

    // Memproses data login
    public function loginCheck(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectAfterLogin();
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->withInput();
    }

    // Memproses data pendaftaran
    public function registerCheck(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'usertype' => 'user',
        ]);

        app(CreditController::class)->grantRegisterBonus($user);

        Auth::login($user);

        return $this->redirectAfterLogin();
    }

    // Menampilkan halaman dasbor pengguna
    public function goDashboard()
    {
        return view('dashboard');
    } 

    // Memproses logout
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // Fungsi helper untuk mengarahkan pengguna setelah login
    private function redirectAfterLogin(): RedirectResponse
    {
        if (Auth::check() && Auth::user()->usertype === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('gallery');
    }
}
