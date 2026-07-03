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
            $sebutanAdmin = 'Admin '.(auth()->user()?->desa?->jenisDesa?->nama ?? 'Desa');

            return redirect()->route('portal.umkm.ajukan')
                ->with('info', "Menu itu khusus Pemilik UMKM — ajukan aksesnya lewat halaman ini, atau hubungi {$sebutanAdmin}.");
        }

        return $next($request);
    }
}
