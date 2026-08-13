<?php

namespace App\Support;

use App\Models\Nagari;

/**
 * Konteks "nagari yang sedang dikelola" oleh super admin, SATU KEY PER MENU
 * (namespace), bukan satu key global.
 *
 * Key global membuat pilihan nagari di satu menu membocor ke menu lain yang tak
 * terkait, termasuk menyembunyikan pemilih nagari yang membaca managedNagariId()
 * sebagai sinyal "konteks sudah ada". Halaman SDGs dan Cuaca tidak memakai kelas
 * ini sama sekali; keduanya mandiri lewat properti Livewire sendiri.
 *
 * Disimpan di session, hilang saat logout. Operator nagari tidak memakainya.
 */
class NagariContext
{
    public const WARGA = 'warga';

    public const UMKM_PROFIL = 'umkm_profil';

    public const UMKM_PRODUK = 'umkm_produk';

    public const LMS_REKAP = 'lms_rekap';

    public const DASHBOARD = 'dashboard';

    private const KEY_PREFIX = 'managed_nagari_id_';

    public static function set(string $namespace, int $nagariId): void
    {
        session()->put(self::KEY_PREFIX.$namespace, $nagariId);
    }

    public static function clear(string $namespace): void
    {
        session()->forget(self::KEY_PREFIX.$namespace);
    }

    /** Bersihkan SEMUA namespace — dipakai saat membuka daftar Nagari (titik awal segar). */
    public static function clearAll(): void
    {
        foreach ([self::WARGA, self::UMKM_PROFIL, self::UMKM_PRODUK, self::LMS_REKAP, self::DASHBOARD] as $namespace) {
            self::clear($namespace);
        }
    }

    public static function id(string $namespace): ?int
    {
        $id = session(self::KEY_PREFIX.$namespace);

        return $id ? (int) $id : null;
    }

    public static function nagari(string $namespace): ?Nagari
    {
        $id = self::id($namespace);

        return $id ? Nagari::find($id) : null;
    }

    /**
     * Bila menu ITU belum punya konteks, pilih nagari pertama (urut nama) secara
     * otomatis, supaya klik langsung dari sidebar tak pernah buntu. Operator nagari
     * & konteks yang sudah ada tak tersentuh.
     *
     * Dipanggil dari mount() halaman superadmin yang memakai konteks (Warga, Profil
     * UMKM, Produk UMKM, Rekap SLC, Dasbor) dan dari ScopedToNagari: widget dimuat
     * lewat request Livewire sendiri, jadi ia tidak bisa bersandar pada mount()
     * halaman yang menampungnya.
     */
    public static function ensureDefault(string $namespace): void
    {
        if (self::id($namespace) !== null) {
            return;
        }

        $nagariId = Nagari::query()->orderBy('nama')->value('id');

        if ($nagariId !== null) {
            self::set($namespace, $nagariId);
        }
    }
}
