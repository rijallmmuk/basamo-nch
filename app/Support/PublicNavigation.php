<?php

namespace App\Support;

use App\Models\Nagari;

/**
 * Sumber tunggal navigasi situs publik.
 *
 * Header, panel navigasi ponsel, dan footer WAJIB membaca dari sini. Sebelumnya
 * ketiganya menyusun daftarnya masing-masing di dalam layout, lengkap dengan
 * pengulangan pemilihan rute subdomain versus rute fallback `/n/{slug}` sebanyak
 * lima kali. Setiap penambahan halaman berarti menyunting tiga tempat dan berisiko
 * satu di antaranya terlewat.
 *
 * DUA BENTUK ALAMAT. Di produksi tiap nagari memakai subdomain `{slug}.domain`;
 * di pengembangan lokal tanpa wildcard DNS dipakai `/n/{slug}`. Seluruh tautan
 * harus mengikuti bentuk yang SEDANG dipakai pengunjung, kalau tidak ia terlempar
 * ke host yang belum tentu ada. Pemilihannya dipusatkan di {@see rute()}.
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
                'route' => 'public.nagari.slc*',
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'href' => self::rute('public.nagari.umkm', $nagari),
                'label' => 'Lapau Nagari',
                'route' => 'public.nagari.umkm*',
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
     * Situs induk. Empat ruang data/katalog punya halaman lintas nagari; Peta
     * tetap berupa bagian beranda karena merupakan pengantar jaringan mitra.
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
                'href' => route('public.iot'),
                'label' => 'IoT',
                'route' => 'public.iot',
                'icon' => 'heroicon-o-cpu-chip',
            ],
            [
                'href' => route('public.home').'#peta',
                'label' => 'Peta Nagari',
                'route' => null,
                'icon' => 'heroicon-o-map',
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
     * WAJIB dari APP_URL, BUKAN route('public.home'). Rute publik tidak terikat
     * domain, sehingga saat dipanggil dari subdomain nagari ia membangkitkan host
     * subdomain itu sendiri, dan tautan "menuju induk" justru berputar kembali ke
     * halaman nagari yang sama.
     */
    public static function indukUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
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
