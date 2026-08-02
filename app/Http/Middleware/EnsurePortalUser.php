<?php

namespace App\Http\Middleware;

use App\Enums\ActiveStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Admin/Operator/Pengajar/DPMD yang membuka portal tidak di-logout
        if ($user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])) {
            return $next($request);
        }

        if (! $user->hasRole('warga')) {
            $this->terminateSession($request);

            return redirect()->route('login')
                ->with('error', 'Akun ini tidak memiliki akses portal warga.');
        }

        // Nonaktif di tengah sesi (admin menonaktifkan) → keluarkan langsung.
        if (auth()->user()->status !== ActiveStatus::Active) {
            $this->terminateSession($request);

            return redirect()->route('login')
                ->with('error', 'Akun Anda nonaktif. Hubungi Operator Nagari.');
        }

        // Warga tanpa nagari = invariant rusak (portal kosong) → bekukan akses.
        if (auth()->user()->nagari_id === null) {
            $this->terminateSession($request);

            return redirect()->route('login')
                ->with('error', 'Akun Anda belum terhubung ke nagari. Hubungi Operator Nagari.');
        }

        // Nagari nonaktif di tengah sesi → bekukan akses.
        if (auth()->user()->nagari?->status !== ActiveStatus::Active) {
            $this->terminateSession($request);

            return redirect()->route('login')
                ->with('error', 'Nagari Anda sedang dinonaktifkan dari sistem. Akses dibekukan sementara.');
        }

        // Login pertama dengan password awal bersama: warga dikunci di BERANDA, tempat
        // modal pemblokir "Buat kata sandi baru" muncul. Sengaja bukan halaman terpisah
        // supaya ia lebih dulu melihat login-nya berhasil. Pola yang sama dipakai panel
        // (lihat EnsureAdminPasswordChanged). Yang tetap boleh lewat hanya beranda itu
        // sendiri dan endpoint penyimpan sandinya; tanpa pengecualian kedua, modal akan
        // memantulkan submit-nya sendiri dan sandi tak pernah bisa diganti.
        if (
            auth()->user()->must_change_password
            && ! $request->routeIs('portal.home')
            && ! $request->routeIs('portal.password.update')
        ) {
            return redirect()->route('portal.home');
        }

        return $next($request);
    }

    private function terminateSession(Request $request): void
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
