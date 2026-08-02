<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (config('production.security_headers', []) as $name => $value) {
            $response->headers->set($name, $value);
        }

        // Halaman akun/panel/portal tidak boleh muncul di hasil pencarian. Header
        // tetap bekerja untuk respons non-HTML dan halaman Filament yang tidak
        // memakai layout publik. Route publik dan endpoint SEO dikecualikan.
        $routeName = (string) $request->route()?->getName();
        if ((! str_starts_with($routeName, 'public.') && ! str_starts_with($routeName, 'seo.'))
            || $routeName === 'public.peta.data') {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($request->isSecure() && config('production.hsts.enabled')) {
            $maxAge = (int) config('production.hsts.max_age', 31536000);
            $includeSubDomains = config('production.hsts.include_subdomains') ? '; includeSubDomains' : '';
            $preload = config('production.hsts.preload') ? '; preload' : '';

            $response->headers->set(
                'Strict-Transport-Security',
                "max-age={$maxAge}{$includeSubDomains}{$preload}",
            );
        }

        return $response;
    }
}
