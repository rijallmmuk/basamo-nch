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
