<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kunci admin yang masih memakai password awal bersama ke dashboard, tempat modal pemblokir
 * "Ganti Kata Sandi" muncul (render hook BODY_END → komponen ForcePasswordChange).
 * Sampai sandi diganti, admin tak bisa membuka halaman lain. Mengganti sandi otomatis
 * melepas flag wajib-ganti (hook User::saving). Dashboard dan logout tetap dapat diakses.
 */
class EnsureAdminPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_password
            && ! $request->routeIs('filament.panel.pages.dashboard')
            && ! $request->routeIs('filament.panel.auth.logout')
        ) {
            return redirect()->route('filament.panel.pages.dashboard');
        }

        return $next($request);
    }
}
