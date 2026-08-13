<?php

namespace App\Http\Middleware;

use App\Services\SharedAccountSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSessionTracked
{
    public function __construct(private readonly SharedAccountSessionService $sharedSession) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user === null
            || ! $user->hasAnyRole(['superadmin', 'operator'])
            || $this->sharedSession->isIdentified($request)
        ) {
            return $next($request);
        }

        // Masuk kembali lewat cookie "Ingat saya" berarti sesi BARU yang sah, bukan
        // sesi lama yang lolos dari pengauditan. Sesi ini diberi referensi auditnya
        // sendiri agar jejaknya tetap utuh.
        if (Auth::viaRemember()) {
            $this->sharedSession->start($user, $request);

            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['login' => 'Sesi Anda sudah berakhir. Silakan masuk kembali.']);
    }
}
