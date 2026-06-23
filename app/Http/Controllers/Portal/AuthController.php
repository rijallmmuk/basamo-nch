<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ActiveStatus;
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

        // Warga login via NIK (kolom `nik`); email dideteksi via format.
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'nik';

        if (! Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['login' => 'NIK/email atau sandi salah.'])
                ->withInput();
        }

        if (Auth::user()->role !== 'warga') {
            Auth::logout();

            return back()
                ->withErrors(['login' => 'Akun ini tidak memiliki akses portal warga.'])
                ->withInput();
        }

        // Akun nonaktif diblokir: menonaktifkan warga = cabut akses portal.
        if (Auth::user()->status !== ActiveStatus::Active) {
            Auth::logout();

            return back()
                ->withErrors(['login' => 'Akun Anda nonaktif. Hubungi Admin Desa.'])
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
