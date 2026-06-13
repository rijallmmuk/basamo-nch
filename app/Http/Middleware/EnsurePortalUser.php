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

        if (! in_array(auth()->user()->role, ['warga', 'umkm_owner'])) {
            auth()->logout();

            return redirect()->route('portal.login')
                ->with('error', 'Akun ini tidak memiliki akses portal warga.');
        }

        return $next($request);
    }
}
