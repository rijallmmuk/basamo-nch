<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureNagariSiteMatchesUser;
use App\Http\Middleware\EnsurePortalUser;
use App\Http\Middleware\PreventCachedHistory;
use App\Http\Middleware\TrustConfiguredProxies;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->replace(
            TrustProxies::class,
            TrustConfiguredProxies::class,
        );
        $middleware->append(AddSecurityHeaders::class);

        // Route publik sengaja tidak terikat domain agar login & portal dapat
        // dibuka dari subdomain nagari mana pun. Efek sampingnya: host APA PUN
        // yang diarahkan ke server ini akan dilayani, sehingga situs bisa tampil
        // di bawah domain milik orang lain, dan `route()` yang memakai host
        // request ikut membangkitkan tautan ke domain itu.
        //
        // Daftar putih ini menutupnya: hanya domain induk dan subdomainnya.
        // Laravel menonaktifkan TrustHosts di environment `local` dan saat test,
        // jadi pengembangan lokal serta suite Pest tidak terpengaruh.
        // Polanya ditulis lengkap (apex + semua subdomain) alih-alih mengandalkan
        // `subdomains: true`, karena opsi itu menurunkan pola dari APP_URL. Bila
        // APP_URL dan PUBLIC_BASE_DOMAIN suatu saat berbeda, subdomain nagari
        // akan tertolak diam-diam dan seluruh situs nagari mati.
        $middleware->trustHosts(at: function (): array {
            $domain = (string) config('app.public_base_domain');

            if ($domain === '' || $domain === 'localhost') {
                return [];
            }

            return ['^(.+\.)?'.preg_quote($domain, '#').'$'];
        }, subdomains: true);

        // Batas situs nagari dipasang ke SELURUH grup web, bukan ditempel per route.
        // Audit route membuktikan cara per-route gampang bolong: tujuh route
        // terautentikasi (pratinjau panel, peta batas, buka notifikasi) sempat lolos
        // karena berada di luar grup portal. Dengan dipasang di sini, route baru
        // otomatis terjaga; middleware-nya sendiri yang melepas route `public.*`.
        // Ditaruh TEPAT SEBELUM SubstituteBindings, bukan sekadar di akhir grup.
        // Kalau binding jalan lebih dulu, permintaan ke record yang tidak ada
        // membalas 404 sementara record yang ada membalas 403, dan selisih itu
        // memberi tahu orang luar mana data yang eksis. Batas situs harus diputus
        // sebelum satu pun record dicari.
        $middleware->web(
            remove: [SubstituteBindings::class],
            append: [EnsureNagariSiteMatchesUser::class, SubstituteBindings::class],
        );

        $middleware->alias([
            'portal' => EnsurePortalUser::class,
            'situs-nagari' => EnsureNagariSiteMatchesUser::class,
            'no-store' => PreventCachedHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
        $exceptions->dontReportDuplicates();
        $exceptions->throttle(function (Throwable $exception): Limit {
            $perMinute = (int) config('production.exception_reports_per_minute', 60);

            return $perMinute > 0
                ? Limit::perMinute($perMinute)->by($exception::class)
                : Limit::none();
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
