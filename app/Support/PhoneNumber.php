<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Pola tunggal validasi nomor telepon/WhatsApp.
     *
     * Pakai `regex:'.PhoneNumber::REGEX` pada array validate(), atau
     * `->regex(PhoneNumber::REGEX)` pada TextInput Filament.
     */
    public const REGEX = '/^\+?[0-9][0-9 ().\-\/]{6,18}$/';

    /**
     * Normalisasi nomor Indonesia ke format internasional `62xxxx` (tanpa `+`/`0` depan).
     * Menerima `0812…`, `+62812…`, `62812…`, atau yang mengandung spasi/strip.
     * Mengembalikan null bila kosong/tak ada digit.
     */
    public static function normalize(?string $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        // Buang non-digit lalu semua nol di depan (tangani 0, 00, +62 sekaligus).
        $number = preg_replace('/^0+/', '', preg_replace('/\D/', '', $raw));

        if (blank($number)) {
            return null;
        }

        return match (true) {
            str_starts_with($number, '62') => $number,    // sudah kode negara
            str_starts_with($number, '8') => '62'.$number, // 8xx (eks-0) → 628xx
            default => $number,
        };
    }
}
