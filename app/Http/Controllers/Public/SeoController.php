<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Support\PublicSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(Request $request): Response
    {
        $lines = PublicSeo::isKnownHost($request)
            ? [
                'User-agent: *',
                'Allow: /',
                'Disallow: /panel/',
                'Disallow: /portal/',
                'Disallow: /notifikasi/',
                'Disallow: /livewire/',
                '',
                'Sitemap: '.$request->getSchemeAndHttpHost().'/sitemap.xml',
              ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function sitemap(Request $request): Response
    {
        abort_unless(PublicSeo::isKnownHost($request), 404);

        $nagari = Nagari::fromHost($request->getHost());
        $urls = $nagari ? $this->nagariUrls($nagari) : $this->globalUrls();

        return response()->view('seo.sitemap', ['urls' => $urls], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** @return list<array{loc: string, lastmod: string|null}> */
    private function globalUrls(): array
    {
        $root = rtrim((string) config('app.url'), '/');
        $urls = collect([
            ['loc' => $root, 'lastmod' => null],
            ['loc' => $root.'/teras-nagari', 'lastmod' => null],
            ['loc' => $root.'/medan-nan-balinduang', 'lastmod' => null],
            ['loc' => $root.'/lapau-nagari', 'lastmod' => null],
            ['loc' => $root.'/iot', 'lastmod' => null],
        ]);

        Pelatihan::query()
            ->ready()
            ->withPublicAudience()
            ->select(['id', 'updated_at'])
            ->orderBy('id')
            ->each(fn (Pelatihan $pelatihan) => $urls->push([
                'loc' => $root.'/medan-nan-balinduang/'.$pelatihan->getRouteKey(),
                'lastmod' => $pelatihan->updated_at?->toAtomString(),
            ]));

        return $urls->unique('loc')->values()->all();
    }

    /** @return list<array{loc: string, lastmod: string|null}> */
    private function nagariUrls(Nagari $nagari): array
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        $lastmod = $nagari->updated_at?->toAtomString();
        $urls = collect([
            ['loc' => route('public.nagari.home', $nagari), 'lastmod' => $lastmod],
            ['loc' => route('public.nagari.teras', $nagari), 'lastmod' => $lastmod],
            ['loc' => route('public.nagari.slc', $nagari), 'lastmod' => $lastmod],
            ['loc' => route('public.nagari.umkm', $nagari), 'lastmod' => $lastmod],
        ]);

        UmkmProfile::query()
            ->where('nagari_id', $nagari->getKey())
            ->where('status', ActiveStatus::Active)
            ->select(['id', 'nagari_id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->each(fn (UmkmProfile $profile) => $urls->push([
                'loc' => route('public.nagari.umkm.etalase', [$nagari, $profile]),
                'lastmod' => $profile->updated_at?->toAtomString(),
            ]));

        UmkmProduct::query()
            ->whereHas('umkmProfile', fn (Builder $profiles) => $profiles
                ->where('nagari_id', $nagari->getKey())
                ->where('status', ActiveStatus::Active))
            ->select(['id', 'umkm_profile_id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->each(fn (UmkmProduct $product) => $urls->push([
                'loc' => route('public.nagari.produk', [$nagari, $product]),
                'lastmod' => $product->updated_at?->toAtomString(),
            ]));

        return $urls->unique('loc')->values()->all();
    }
}
