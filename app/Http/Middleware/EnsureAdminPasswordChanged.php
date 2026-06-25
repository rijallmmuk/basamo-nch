<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kunci admin yang masih memakai sandi awal (OTP) ke dashboard, tempat modal pemblokir
 * "Ganti Kata Sandi" muncul (render hook BODY_END → komponen ForcePasswordChange).
 * Sampai sandi diganti, admin tak bisa membuka halaman lain. Mengganti sandi otomatis
 * menghapus OTP (hook User::saving). Livewire (submit modal) & logout dibiarkan lewat.
 */
class EnsureAdminPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_password
            && ! $request->routeIs('filament.admin.pages.dashboard')
            && ! $request->routeIs('filament.admin.auth.logout')
            && ! $request->routeIs('livewire.*')
        ) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return $next($request);
    }
}
