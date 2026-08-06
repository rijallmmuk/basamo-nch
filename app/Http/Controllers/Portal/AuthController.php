<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Nagari;
use App\Services\SharedAccountSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(private readonly SharedAccountSessionService $sharedSession) {}

    public function showLogin(Request $request): View
    {
        return view('portal.auth.login', [
            'situsNagari' => Nagari::fromHost($request->getHost()),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $identityKey = Str::lower($credentials['login']).'|'.$request->ip();
        $ipKey = 'login-ip|'.$request->ip();
        $maxPerIdentity = (int) config('production.login_throttle.per_identity');
        $maxPerIp = (int) config('production.login_throttle.per_ip');

        // Dua rem: bertarget (per identitas+IP) DAN lintas-identitas (per IP) untuk
        // menutup password-spraying yang tak terhitung oleh rem bertarget.
        if (RateLimiter::tooManyAttempts($identityKey, $maxPerIdentity)
            || RateLimiter::tooManyAttempts($ipKey, $maxPerIp)) {
            $seconds = max(RateLimiter::availableIn($identityKey), RateLimiter::availableIn($ipKey));

            return back()
                ->withErrors(['login' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."])
                ->withInput();
        }

        // Hitung percobaan ini ke anggaran IP APA PUN hasilnya (semprotan = banyak sukses).
        RateLimiter::hit($ipKey, 60);

        // Deteksi jenis identitas: 16 digit → NIK warga; selain itu → username admin
        // (kode nagari ±10 digit, tak pernah 16 → tak bentrok). Email BUKAN identitas login.
        $login = $credentials['login'];
        $field = ctype_digit($login) && strlen($login) === 16 ? 'nik' : 'username';

        // Umur cookie "Ingat saya" ditetapkan sendiri; bawaan Laravel 400 hari terlalu
        // panjang untuk akun yang dipakai bersama.
        Auth::guard('web')->setRememberDuration((int) config('production.remember_days') * 24 * 60);

        if (! $this->cobaMasuk($field, $login, $credentials['password'], $request->boolean('remember'))) {
            RateLimiter::hit($identityKey, 60);

            return back()
                ->withErrors(['login' => 'NIK, username, atau kata sandi salah.'])
                ->withInput();
        }

        $user = Auth::user();

        // Akun nonaktif diblokir (berlaku untuk semua peran).
        if ($user->status !== ActiveStatus::Active) {
            $this->terminateSession($request);

            return back()
                ->withErrors(['login' => 'Akun Anda sedang dinonaktifkan. Hubungi admin yang membuat akun Anda.'])
                ->withInput();
        }

        // Back-office (superadmin/operator/pengajar/dpmd) diarahkan ke /panel dan boleh
        // tak terikat nagari (superadmin/pengajar/dpmd). Pengguna portal (warga/umkm)
        // WAJIB terhubung ke satu nagari — tanpa nagari, tak ada konten yang menyasarnya.
        $isBackOffice = $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);

        if (! $isBackOffice && $user->nagari_id === null) {
            $this->terminateSession($request);

            return back()
                ->withErrors(['login' => 'Akun Anda belum terhubung ke nagari. Hubungi operator nagari.'])
                ->withInput();
        }

        // Operator wajib memiliki satu tenant. Jangan mengandalkan form pembuatan
        // akun saja: data produksi dapat berasal dari impor atau penyuntingan
        // manual, dan operator tanpa nagari tidak boleh masuk ke query panel.
        if ($user->isOperator() && ! $user->isLintasNagari() && $user->nagari_id === null) {
            $this->terminateSession($request);

            return back()
                ->withErrors(['login' => 'Akun operator belum terhubung ke nagari. Hubungi superadmin.'])
                ->withInput();
        }

        // Nagari nonaktif membekukan login entitasnya (hanya bila pengguna punya nagari).
        if ($user->nagari_id !== null && $user->nagari?->status !== ActiveStatus::Active) {
            $this->terminateSession($request);

            return back()
                ->withErrors(['login' => 'Nagari Anda sedang dinonaktifkan, sehingga akses ikut ditutup sementara.'])
                ->withInput();
        }

        // Batas situs nagari: masuk lewat subdomain nagari lain ditolak di sini,
        // supaya pengguna tidak sempat berstatus login lalu ditembok halaman
        // berikutnya. Middleware `situs-nagari` menjaga sisa permintaannya.
        $situs = Nagari::fromHost($request->getHost());

        if ($situs !== null && ! $user->bolehMasukSitusNagari($situs)) {
            $this->terminateSession($request);

            return back()
                ->withErrors(['login' => "Anda tidak terdaftar di Nagari {$situs->nama}."])
                ->withInput();
        }

        // Peran lintas nagari tidak punya rumah di satu subdomain. Login dari
        // subdomain nagari mana pun dipulangkan ke domain induk, supaya alamat yang
        // tampil tidak menyiratkan mereka sedang berada di dalam nagari tertentu.
        $pulangkanKeInduk = $situs !== null && $user->isLintasNagari();

        // Bersihkan rem bertarget saja; anggaran IP dibiarkan agar semprotan yang
        // sesekali sukses tetap terakumulasi menuju batas.
        RateLimiter::clear($identityKey);
        $request->session()->regenerate();

        if ($pulangkanKeInduk) {
            if ($user->hasAnyRole(['superadmin', 'operator'])) {
                $this->sharedSession->start($user, $request);
            }

            return redirect()->to(rtrim(config('app.url'), '/').'/panel');
        }

        // Akun back-office bersama (superadmin/operator) butuh referensi audit sesi
        // (EnsureAdminSessionTracked). pengajar/dpmd = akun individu → tak diaudit.
        if ($user->hasAnyRole(['superadmin', 'operator'])) {
            $this->sharedSession->start($user, $request);
        }

        // Arahkan ke beranda peran masing-masing. Sengaja TIDAK pakai intended():
        // "intended URL" bisa lintas-area (mis. /panel tersimpan saat tamu, lalu warga
        // login → terlempar ke /panel & ditolak). Redirect tetap per peran lebih aman.
        // Back-office (superadmin/operator/pengajar/dpmd) = panel Filament /panel, tiap
        // Resource menjaga scoping perannya.
        // Multi-role: peran utama (ROLE_PRIORITY) menentukan area tujuan.
        switch ($user->primaryRole()) {
            case 'superadmin':
            case 'operator':
            case 'pengajar':
            case 'dpmd':
                return redirect('/panel');
            case 'warga':
                if ($user->hasUmkmAccess()) {
                    return redirect('/panel');
                }
                return redirect()->route('portal.home');
        }

        // Peran tak dikenal → tolak (defense-in-depth).
        $this->terminateSession($request);

        return back()
            ->withErrors(['login' => 'Akun ini tidak memiliki akses.'])
            ->withInput();
    }

    /**
     * Percobaan masuk yang tidak bisa dijatuhkan oleh satu baris data yang rusak.
     *
     * `Auth::attempt` MELEMPAR RuntimeException, bukan mengembalikan false, bila
     * kolom `password` berisi sesuatu yang bukan hash bcrypt. Itu terjadi nyata di
     * produksi: sandi disunting langsung lewat phpMyAdmin sehingga tersimpan
     * sebagai teks biasa, dan sejak itu setiap percobaan masuk ke akun tersebut
     * dibalas galat 500.
     *
     * Dua akibatnya sama-sama buruk. Akunnya mati total, tidak sekadar salah
     * sandi. Dan halaman masuk jadi membocorkan keberadaan akun, sebab identitas
     * yang tidak ada dibalas normal sedangkan yang hashnya rusak dibalas 500.
     *
     * Di sini hash yang tidak sah diperlakukan sebagai percobaan gagal biasa, lalu
     * dicatat sebagai peringatan supaya barisnya bisa ditemukan dan diperbaiki
     * dengan `php artisan ops:reset-password`.
     */
    private function cobaMasuk(string $field, string $login, string $password, bool $remember): bool
    {
        try {
            return Auth::attempt([$field => $login, 'password' => $password], $remember);
        } catch (RuntimeException $e) {
            Log::warning('Percobaan masuk ditolak: hash kata sandi tidak sah.', [
                'field' => $field,
                // Identitasnya dicatat karena dibutuhkan untuk memperbaiki barisnya.
                // Kata sandi yang diketik TIDAK PERNAH ikut dicatat.
                'login' => $login,
                'alasan' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->terminateSession($request);

        return redirect()->route('login');
    }

    private function terminateSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
