<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Nagari;
use App\Services\SharedAccountSessionService;
use App\Support\Auth\KonteksLogin;
use App\Support\Auth\TujuanSetelahLogin;
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
    public function __construct(
        private readonly SharedAccountSessionService $sharedSession,
        private readonly TujuanSetelahLogin $tujuan,
    ) {}

    public function showLogin(Request $request): View
    {
        return view('portal.auth.login', [
            'situsNagari' => Nagari::fromHost($request->getHost()),
            // Konteks dibawa halaman publik lewat query string, lalu diteruskan
            // sebagai input tersembunyi agar tetap hidup melewati percobaan login
            // yang gagal (`back()` membangun ulang halaman ini tanpa query).
            'konteks' => KonteksLogin::dariRequest($request),
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

        // Batas situs nagari TIDAK LAGI menolak login. Sejak gerbang login ada,
        // siapa pun boleh menekan Masuk dari halaman publik mana pun, termasuk
        // situs nagari tetangga. Yang dijaga bukan lagi TEMPAT ORANG LOGIN
        // melainkan TEMPAT IA MENDARAT: TujuanSetelahLogin memulangkan warga dan
        // operator ke subdomain nagarinya, dan EnsureNagariSiteMatchesUser tetap
        // berdiri sebagai jaring lapis kedua bagi alamat yang diketik manual
        // sesudahnya.

        // Peran tak dikenal → tolak (defense-in-depth). Diperiksa SEBELUM tujuan
        // dihitung: akun tanpa peran tidak punya satu pun area yang menyasarnya.
        if ($user->primaryRole() === null) {
            $this->terminateSession($request);

            return back()
                ->withErrors(['login' => 'Akun ini tidak memiliki akses.'])
                ->withInput();
        }

        // Bersihkan rem bertarget saja; anggaran IP dibiarkan agar semprotan yang
        // sesekali sukses tetap terakumulasi menuju batas.
        RateLimiter::clear($identityKey);
        $request->session()->regenerate();

        // Akun back-office bersama (superadmin/operator) butuh referensi audit sesi
        // (EnsureAdminSessionTracked). pengajar/dpmd = akun individu → tak diaudit.
        if ($user->hasAnyRole(['superadmin', 'operator'])) {
            $this->sharedSession->start($user, $request);
        }

        // Tujuan dihitung dari gerbang DAN identitas. Sengaja tetap TIDAK memakai
        // intended(): URL tersimpan bisa melintasi area, mis. /panel yang tersimpan
        // saat masih tamu lalu dibuka warga, dan berujung penolakan tepat sesudah
        // login berhasil. Gerbang hanya satu kata dari daftar tertutup.
        $tujuan = $this->tujuan->untuk($user, KonteksLogin::dariRequest($request));

        $redirect = redirect()->to($tujuan->url);

        return $tujuan->pesanInfo !== null
            ? $redirect->with('info', $tujuan->pesanInfo)
            : $redirect;
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
