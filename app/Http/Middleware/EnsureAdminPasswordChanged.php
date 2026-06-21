<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa admin yang masih memakai sandi awal (OTP) untuk menggantinya lebih dulu
 * di halaman profil. Mengganti sandi otomatis menghapus OTP (hook User::saving).
 * Permintaan Livewire (mis. submit form profil) & logout dibiarkan lewat.
 */
class EnsureAdminPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_password
            && ! $request->routeIs('filament.admin.auth.profile')
            && ! $request->routeIs('filament.admin.auth.logout')
            && ! $request->routeIs('livewire.*')
        ) {
            return redirect()->route('filament.admin.auth.profile');
        }

        return $next($request);
    }
}
