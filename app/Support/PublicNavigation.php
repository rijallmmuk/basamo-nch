<?php

namespace App\Support;

use App\Enums\GerbangLogin;
use App\Models\Nagari;
use App\Support\Auth\KonteksLogin;

/**
 * Sumber tunggal navigasi situs publik; header, navigasi ponsel, dan footer
 * membaca dari sini.
 *
 * Nagari punya dua bentuk alamat: subdomain `{slug}.domain` di produksi dan
 * `/n/{slug}` sebagai fallback. Tautan harus mengikuti bentuk yang sedang dipakai
 * pengunjung, kalau tidak ia terlempar ke host yang belum tentu ada. Pemilihannya
 * dipusatkan di {@see rute()}.
 */
final class PublicNavigation
{
    /**
     * Item navigasi untuk konteks yang sedang dibuka.
     *
     * @return list<array{href: string, label: string, route: string|list<string>|null, icon: string}>
     */
    public static function items(?Nagari $nagari): array
    {
        return $nagari ? self::itemsNagari($nagari) : self::itemsInduk();
    }

    /**
     * Empat pilar milik satu nagari, masing-masing halaman tersendiri.
     *
     * @return list<array{href: string, label: string, route: string|list<string>|null, icon: string}>
     */
    private static function itemsNagari(Nagari $nagari): array
    {
        return [
            [
                'href' => self::rute('public.nagari.home', $nagari),
                'label' => 'Beranda',
                'route' => 'public.nagari.home*',
                'icon' => 'heroicon-o-home',
            ],
            [
                'href' => self::rute('public.nagari.teras', $nagari),
                'label' => 'Teras Nagari',
                'route' => 'public.nagari.teras*',
                'icon' => 'heroicon-o-presentation-chart-line',
            ],
            [
                'href' => self::rute('public.nagari.slc', $nagari),
                'label' => 'Medan Nan Balinduang',
                // Halaman detail bernama rute tersendiri, bukan turunan `.slc`, jadi
                // polanya harus disebut agar menu tetap menyala di sana.
                'route' => ['public.nagari.slc*', 'public.nagari.pelatihan*'],
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'href' => self::rute('public.nagari.umkm', $nagari),
                'label' => 'Lapau Nagari',
                'route' => ['public.nagari.umkm*', 'public.nagari.produk*'],
                'icon' => 'heroicon-o-building-storefront',
            ],
            [
                'href' => self::rute('public.nagari.bapaneh', $nagari),
                'label' => 'Medan Nan Bapaneh',
                'route' => 'public.nagari.bapaneh*',
                'icon' => 'heroicon-o-sparkles',
            ],
        ];
    }

    /**
     * Situs induk: beranda dan tepat empat pilar. IoT, cuaca, serta rekap belajar
     * merupakan data Teras Nagari, bukan tujuan navigasi yang berdiri sendiri.
     *
     * @return list<array{href: string, label: string, route: string|list<string>|null, icon: string}>
     */
    private static function itemsInduk(): array
    {
        return [
            [
                'href' => route('public.home'),
                'label' => 'Beranda',
                'route' => 'public.home',
                'icon' => 'heroicon-o-home',
            ],
            [
                'href' => route('public.teras'),
                'label' => 'Teras Nagari',
                'route' => 'public.teras',
                'icon' => 'heroicon-o-presentation-chart-line',
            ],
            [
                'href' => route('public.slc'),
                'label' => 'Medan Nan Balinduang',
                'route' => ['public.slc', 'public.pelatihan'],
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'href' => route('public.umkm'),
                'label' => 'Lapau Nagari',
                'route' => ['public.umkm*', 'public.produk'],
                'icon' => 'heroicon-o-building-storefront',
            ],
            [
                'href' => route('public.bapaneh'),
                'label' => 'Medan Nan Bapaneh',
                'route' => 'public.bapaneh',
                'icon' => 'heroicon-o-sparkles',
            ],
        ];
    }

    /** Tautan brand: kembali ke beranda konteks yang sedang dibuka. */
    public static function brandUrl(?Nagari $nagari): string
    {
        return $nagari ? self::rute('public.nagari.home', $nagari) : route('public.home');
    }

    /**
     * URL situs induk untuk tautan lintas situs.
     *
     * WAJIB dari APP_URL, bukan route('public.home'). Rute publik tidak terikat
     * domain, jadi dipanggil dari subdomain nagari ia membangkitkan host subdomain
     * itu sendiri dan tautannya berputar kembali ke halaman yang sama.
     */
    public static function indukUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Alamat tombol Masuk untuk halaman yang sedang dibuka.
     *
     * Gerbang dititipkan lewat URL supaya login mengantar ke area yang sesuai
     * halaman asalnya. Diturunkan dari NAMA ROUTE, bukan header `Referer`, yang
     * dapat dibuang proxy maupun setelan privasi peramban.
     *
     * $gerbang diisi untuk tombol yang maksudnya lebih tegas daripada halamannya,
     * misalnya ajakan masuk portal belajar di beranda nagari yang netral.
     * $pelatihanId membuat tujuannya satu pelatihan tertentu; yang dititipkan
     * hanya ID-nya, alamatnya dirakit setelah hak akses diperiksa.
     */
    public static function masukUrl(?GerbangLogin $gerbang = null, ?int $pelatihanId = null): string
    {
        $gerbang ??= GerbangLogin::dariNamaRute(request()->route()?->getName());

        return route('login', (new KonteksLogin($gerbang, $pelatihanId))->sebagaiParameter());
    }

    /**
     * Rute nagari mengikuti bentuk alamat yang sedang dipakai pengunjung.
     *
     * @param  array<string, mixed>  $tambahan  parameter rute selain nagari
     */
    public static function rute(string $nama, Nagari $nagari, array $tambahan = []): string
    {
        $parameter = ['nagari' => $nagari, ...$tambahan];

        return request()->routeIs('*.fallback')
            ? route($nama.'.fallback', $parameter)
            : route($nama, $parameter);
    }
}
