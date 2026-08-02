<?php

namespace App\Enums;

/**
 * Tiga dimensi penyusun IDM (Indeks Desa Membangun). IDM = rata-rata ketiganya.
 * Backing value = kode PERSIS pada baris subtotal API ("IKS 2024" → 'IKS').
 */
enum DimensiIdm: string
{
    case IKS = 'IKS';
    case IKE = 'IKE';
    case IKL = 'IKL';

    /** Nama lengkap dimensi. */
    public function label(): string
    {
        return match ($this) {
            self::IKS => 'Ketahanan Sosial',
            self::IKE => 'Ketahanan Ekonomi',
            self::IKL => 'Ketahanan Lingkungan',
        };
    }

    /** Kata pendek untuk header ringkas. */
    public function short(): string
    {
        return match ($this) {
            self::IKS => 'Sosial',
            self::IKE => 'Ekonomi',
            self::IKL => 'Lingkungan',
        };
    }

    /** Warna aksen untuk diagram & badge. */
    public function color(): string
    {
        return match ($this) {
            self::IKS => 'info',
            self::IKE => 'warning',
            self::IKL => 'success',
        };
    }
}
