<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi area "Produk Saya" ke warga yang punya kapabilitas UMKM
 * (umkm_access_granted_at terisi). Berjalan setelah middleware `portal`.
 */
class EnsureUmkmOwner
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->user()?->hasUmkmAccess()) {
            return redirect()->route('portal.home')
                ->with('info', 'Menu UMKM hanya untuk Pemilik UMKM. Hubungi Admin Desa untuk mendapatkan akses.');
        }

        return $next($request);
    }
}
