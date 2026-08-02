<?php

namespace App\Support;

use App\Models\Nagari;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use Illuminate\Http\Request;

final class PublicSeo
{
    /** URL utama tanpa query filter, dengan duplikat fallback diarahkan ke URL publik. */
    public static function canonical(Request $request): string
    {
        $route = $request->route();
        $name = (string) $route?->getName();
        $parameters = $route?->parameters() ?? [];

        // Satu pelatihan dapat menyasar banyak nagari. Domain utama menjadi sumber
        // tunggal detailnya; katalog nagari tetap unik dan tetap dapat diindeks.
        if (in_array($name, ['public.nagari.pelatihan', 'public.nagari.pelatihan.fallback'], true)) {
            return route('public.pelatihan', ['pelatihan' => $parameters['pelatihan']]);
        }

        // Setiap UMKM hanya punya satu nagari. Detail global dipertahankan untuk
        // pengalaman jelajah, tetapi sinyal pencarian dikonsolidasikan ke rumahnya.
        if ($name === 'public.umkm.etalase' && ($parameters['umkmProfile'] ?? null) instanceof UmkmProfile) {
            return self::umkmProfile($parameters['umkmProfile']);
        }

        if ($name === 'public.produk' && ($parameters['product'] ?? null) instanceof UmkmProduct) {
            return self::umkmProduct($parameters['product']);
        }

        if (str_ends_with($name, '.fallback')) {
            return route(str($name)->beforeLast('.fallback')->toString(), $parameters);
        }

        $url = $request->url();
        $base = mb_strtolower((string) config('app.public_base_domain'));

        // `www` menyajikan halaman induk yang sama; canonical-nya selalu APP_URL.
        if ($base !== '' && mb_strtolower($request->getHost()) === 'www.'.$base) {
            return rtrim((string) config('app.url'), '/').$request->getPathInfo();
        }

        return $url;
    }

    /** Filter/pencarian/paginasi tidak menjadi halaman indeks tersendiri. */
    public static function robots(Request $request): string
    {
        if ($request->query->count() > 0 || $request->routeIs('public.nagari.bapaneh*')) {
            return 'noindex, follow';
        }

        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public static function umkmProfile(UmkmProfile $profile): string
    {
        $profile->loadMissing('nagari');

        return route('public.nagari.umkm.etalase', [
            'nagari' => $profile->nagari,
            'umkmProfile' => $profile,
        ]);
    }

    public static function umkmProduct(UmkmProduct $product): string
    {
        $product->loadMissing('umkmProfile.nagari');

        return route('public.nagari.produk', [
            'nagari' => $product->umkmProfile->nagari,
            'product' => $product,
        ]);
    }

    public static function isKnownHost(Request $request): bool
    {
        $host = mb_strtolower($request->getHost());
        $base = mb_strtolower((string) config('app.public_base_domain'));

        return $base === 'localhost'
            || $host === $base
            || $host === 'www.'.$base
            || Nagari::fromHost($host)?->status?->value === 'active';
    }
}
