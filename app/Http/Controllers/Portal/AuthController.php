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
            'login' => 'required|string',     // NIK (warga) / username / email (admin)
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

        // Deteksi jenis identitas: email (ada '@') → email; 16 digit → NIK warga;
        // selain itu → username admin (kode nagari ±10 digit, tak pernah 16 → tak bentrok).
        $login = $credentials['login'];
        $field = match (true) {
            filter_var($login, FILTER_VALIDATE_EMAIL) !== false => 'email',
            ctype_digit($login) && strlen($login) === 16 => 'nik',
            default => 'username',
        };

        if (! Auth::attempt([$field => $login, 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['login' => 'Identitas atau kata sandi salah.'])
                ->withInput();
        }

        $user = Auth::user();

        // Akun nonaktif diblokir (berlaku untuk semua peran).
        if ($user->status !== ActiveStatus::Active) {
            Auth::logout();

            return back()
                ->withErrors(['login' => 'Akun Anda nonaktif. Hubungi admin.'])
                ->withInput();
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        // Arahkan ke beranda peran masing-masing. Sengaja TIDAK pakai intended():
        // "intended URL" bisa lintas-area (mis. /admin tersimpan saat tamu, lalu warga
        // login → terlempar ke /admin & ditolak). Redirect tetap per peran lebih aman.
        if (in_array($user->role, ['super_admin', 'desa_admin'], true)) {
            return redirect('/admin');
        }

        if ($user->role === 'warga') {
            return redirect()->route('portal.home');
        }

        // Peran tak dikenal → tolak (defense-in-depth).
        Auth::logout();

        return back()
            ->withErrors(['login' => 'Akun ini tidak memiliki akses.'])
            ->withInput();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
