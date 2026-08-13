<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Platform tautan promosi UMKM (sosmed & e-commerce yang dimiliki pemilik).
 * Dipakai pada kolom JSON `tautan` di umkm_profiles & umkm_products.
 *
 * Ikon memakai Heroicons berbasis KATEGORI (bukan logo merek) sesuai aturan proyek
 * "Heroicons saja"; identitas platform ditegaskan lewat label teks.
 */
enum TautanPlatform: string implements HasLabel
{
    // Sosial media
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case X = 'x';
    case Telegram = 'telegram';

    // E-commerce / marketplace
    case Shopee = 'shopee';
    case Tokopedia = 'tokopedia';
    case Lazada = 'lazada';
    case TikTokShop = 'tiktok_shop';
    case Bukalapak = 'bukalapak';
    case Blibli = 'blibli';

    // Web & lainnya
    case Website = 'website';
    case GoogleMaps = 'google_maps';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
            self::X => 'X (Twitter)',
            self::Telegram => 'Telegram',
            self::Shopee => 'Shopee',
            self::Tokopedia => 'Tokopedia',
            self::Lazada => 'Lazada',
            self::TikTokShop => 'TikTok Shop',
            self::Bukalapak => 'Bukalapak',
            self::Blibli => 'Blibli',
            self::Website => 'Website',
            self::GoogleMaps => 'Google Maps',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Domain yang sah bagi platform ini; daftar kosong berarti bebas.
     *
     * Cukup didaftar domain induknya. Pencocokan di {@see menerimaUrl()} menerima
     * subdomain dan menolak domain yang sekadar berakhiran sama.
     *
     * @return list<string>
     */
    public function hosts(): array
    {
        return match ($this) {
            self::Instagram => ['instagram.com', 'instagr.am'],
            self::Facebook => ['facebook.com', 'fb.com', 'fb.me', 'fb.watch'],
            self::TikTok, self::TikTokShop => ['tiktok.com'],
            self::YouTube => ['youtube.com', 'youtu.be'],
            self::X => ['x.com', 'twitter.com'],
            self::Telegram => ['t.me', 'telegram.me', 'telegram.org'],
            self::Shopee => ['shopee.co.id', 'shopee.com', 'shp.ee'],
            self::Tokopedia => ['tokopedia.com', 'tokopedia.link'],
            self::Lazada => ['lazada.co.id', 'lazada.com'],
            self::Bukalapak => ['bukalapak.com'],
            self::Blibli => ['blibli.com'],
            self::GoogleMaps => ['google.com', 'goo.gl', 'app.goo.gl'],
            // Jalan keluar bagi alamat di luar daftar platform, supaya pemilik tidak
            // perlu memilih platform yang salah demi lolos validasi.
            self::Website, self::Lainnya => [],
        };
    }

    /** Contoh alamat yang benar, dipakai pada pesan galat dan placeholder. */
    public function contohUrl(): string
    {
        return match ($this) {
            self::Instagram => 'https://instagram.com/namausaha',
            self::Facebook => 'https://facebook.com/namausaha',
            self::TikTok => 'https://tiktok.com/@namausaha',
            self::TikTokShop => 'https://shop.tiktok.com/namausaha',
            self::YouTube => 'https://youtube.com/@namausaha',
            self::X => 'https://x.com/namausaha',
            self::Telegram => 'https://t.me/namausaha',
            self::Shopee => 'https://shopee.co.id/namausaha',
            self::Tokopedia => 'https://tokopedia.com/namausaha',
            self::Lazada => 'https://lazada.co.id/shop/namausaha',
            self::Bukalapak => 'https://bukalapak.com/u/namausaha',
            self::Blibli => 'https://blibli.com/merchant/namausaha',
            self::GoogleMaps => 'https://maps.app.goo.gl/xxxxx',
            self::Website => 'https://namausaha.com',
            self::Lainnya => 'https://alamat-lengkap-anda',
        };
    }

    /** Alamat ini benar-benar milik platform yang dipilih? */
    public function menerimaUrl(string $url): bool
    {
        $hosts = $this->hosts();

        if ($hosts === []) {
            return true;
        }

        $host = mb_strtolower((string) parse_url(trim($url), PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        $host = preg_replace('/^www\./', '', $host) ?? $host;

        foreach ($hosts as $sah) {
            if ($host === $sah || str_ends_with($host, '.'.$sah)) {
                return true;
            }
        }

        return false;
    }

    /** Kategori untuk pengelompokan opsi & penataan tampilan publik. */
    public function category(): string
    {
        return match ($this) {
            self::Instagram, self::Facebook, self::TikTok, self::YouTube, self::X, self::Telegram => 'Sosial Media',
            self::Shopee, self::Tokopedia, self::Lazada, self::TikTokShop, self::Bukalapak, self::Blibli => 'E-commerce',
            self::Website, self::GoogleMaps, self::Lainnya => 'Web & Lainnya',
        };
    }

    /** Nama ikon Heroicon (outline) berbasis kategori. */
    public function icon(): string
    {
        return match ($this) {
            self::Website => 'heroicon-o-globe-alt',
            self::GoogleMaps => 'heroicon-o-map-pin',
            self::Shopee, self::Tokopedia, self::Lazada, self::TikTokShop, self::Bukalapak, self::Blibli => 'heroicon-o-shopping-bag',
            self::Instagram, self::Facebook, self::TikTok, self::YouTube, self::X, self::Telegram => 'heroicon-o-chat-bubble-left-right',
            self::Lainnya => 'heroicon-o-link',
        };
    }

    /**
     * Opsi Filament dikelompokkan per kategori (optgroup): [kategori => [value => label]].
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedOptions(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->category()][$case->value] = $case->getLabel();
        }

        return $grouped;
    }
}
