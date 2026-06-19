<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => 'required|string',     // NIK (warga) atau email
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($credentials['login']).'|'.$request->ip();

        // Anti brute-force: maks 5 percobaan / menit per identitas+IP.
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['login' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."])
                ->withInput();
        }

        // NIK disimpan di kolom username; email dideteksi via format.
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['login' => 'NIK/email atau sandi salah.'])
                ->withInput();
        }

        if (! in_array(Auth::user()->role, ['warga', 'umkm_owner'])) {
            Auth::logout();

            return back()
                ->withErrors(['login' => 'Akun ini tidak memiliki akses portal warga.'])
                ->withInput();
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route('portal.home');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
