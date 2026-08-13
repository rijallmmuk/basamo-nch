<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Asal sertifikat sebuah pelatihan.
 *
 * Sebagian penyelenggara sudah punya berkas sertifikat sendiri, satu berkas untuk
 * seluruh peserta, sehingga sistem cukup membagikannya. Sisanya menerbitkan
 * sertifikat per warga lengkap dengan nomor seri dan halaman verifikasi.
 */
enum ModeSertifikat: string implements HasLabel
{
    case Tidak = 'tidak';
    case Terbit = 'terbit';
    case Unggah = 'unggah';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tidak => 'Tanpa sertifikat',
            self::Terbit => 'Diterbitkan sistem',
            self::Unggah => 'Berkas dari penyelenggara',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Tidak => 'Pelatihan ini tidak memberi sertifikat.',
            self::Terbit => 'Sistem menerbitkan sertifikat atas nama tiap warga, bernomor seri dan dapat diperiksa keasliannya lewat halaman publik.',
            self::Unggah => 'Warga mengunduh berkas yang Anda unggah. Satu berkas dipakai seluruh peserta, jadi jangan mencantumkan nama peserta di dalamnya.',
        };
    }

    public function memberiSertifikat(): bool
    {
        return $this !== self::Tidak;
    }
}
