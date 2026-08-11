<?php

namespace App\Support;

use App\Enums\GerbangLogin;
use App\Models\Nagari;
use App\Support\Auth\KonteksLogin;

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
     * Alamat tombol Masuk untuk halaman yang SEDANG dibuka.
     *
     * Halaman publik bukan cuma pintu masuk yang seragam: orang yang menekan
     * Masuk di Lapau Nagari sedang memikirkan lapaknya, yang menekannya di Medan
     * Nan Balinduang sedang memikirkan pelatihan. Gerbang itulah yang dititipkan
     * di sini supaya login dapat mengantar ke area yang tepat.
     *
     * Diturunkan dari NAMA ROUTE halaman, bukan dari header `Referer`. Referer
     * dapat dibuang proxy maupun setelan privasi peramban, dan fitur ini akan
     * gagal secara acak tanpa jejak kalau bergantung padanya.
     *
     * $gerbang boleh diisi untuk tombol yang maksudnya lebih tegas daripada
     * halamannya, misalnya ajakan masuk portal belajar yang berdiri di beranda
     * nagari. Beranda itu sendiri netral, tombolnya tidak.
     *
     * $pelatihanId membuat tautannya menunjuk satu pelatihan tertentu, sehingga
     * orang yang menekan Masuk dari halaman muka sebuah pelatihan mendarat di
     * pelatihan itu juga, bukan di beranda portal. Yang dititipkan cuma ID-nya;
     * alamatnya dirakit di sisi kita setelah hak aksesnya diperiksa.
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
