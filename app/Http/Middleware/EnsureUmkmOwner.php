<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi area "Produk Saya" hanya untuk Pemilik UMKM (umkm_owner).
 * Berjalan setelah middleware `portal` (auth + paksa ganti sandi).
 */
class EnsureUmkmOwner
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->role !== 'umkm_owner') {
            return redirect()->route('portal.home')
                ->with('info', 'Menu UMKM hanya untuk Pemilik UMKM. Hubungi Admin Nagari untuk mendapatkan akses.');
        }

        return $next($request);
    }
}
