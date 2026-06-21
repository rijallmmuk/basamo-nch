<?php

namespace App\Http\Middleware;

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
            return redirect()->route('portal.login');
        }

        if (auth()->user()->role !== 'warga') {
            auth()->logout();

            return redirect()->route('portal.login')
                ->with('error', 'Akun ini tidak memiliki akses portal warga.');
        }

        // Nonaktif di tengah sesi (admin menonaktifkan) → keluarkan langsung.
        if (auth()->user()->status !== 'active') {
            auth()->logout();

            return redirect()->route('portal.login')
                ->with('error', 'Akun Anda nonaktif. Hubungi Admin Desa.');
        }

        // Login pertama dengan OTP: wajib ganti sandi sebelum mengakses portal.
        if (auth()->user()->must_change_password && ! $request->routeIs('portal.password.*')) {
            return redirect()->route('portal.password.edit');
        }

        return $next($request);
    }
}
