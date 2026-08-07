<?php

namespace App\Support;

use App\Models\Nagari;

/**
 * Konteks "nagari yang sedang dikelola" oleh super admin — SATU KEY PER MENU (namespace),
 * bukan satu key global. Dulu (sebelum 2026-07-14) satu key dipakai bersama lintas
 * menu (Warga/Wilayah/UMKM), akibatnya memilih nagari di satu menu ikut "membocor" ke
 * menu lain yang tak terkait (termasuk menyembunyikan picker SDGs/Cuaca yang membaca
 * managedNagariId() sbg sinyal "sudah ada konteks, jangan tampilkan picker sendiri") —
 * bug dilaporkan user. Sekarang: tiap menu (WARGA/UMKM_PROFIL/UMKM_PRODUK)
 * punya key sesi sendiri, sepenuhnya independen satu sama lain. Menu lain (SDGs,
 * Cuaca) TIDAK memakai kelas ini sama sekali — mereka mandiri lewat properti
 * Livewire ($nagariId) sendiri. Dasbor MEMAKAI kelas ini (namespace DASHBOARD)
 * sejak 2026-07-31: superadmin dan DPMD menganalisis satu nagari terpilih, bukan
 * rata-rata seluruh nagari.
 * Disimpan di session (per-sesi, hilang saat logout). Operator nagari tak memakainya.
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
