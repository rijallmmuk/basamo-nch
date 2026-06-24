<?php

namespace App\Support;

use App\Models\Desa;

/**
 * Konteks "desa yang sedang dikelola" oleh super admin — dipakai saat super admin
 * masuk ke halaman Warga sebuah desa lewat aksi "Kelola Warga" di menu Desa.
 * Disimpan di session (per-sesi, hilang saat logout). Admin desa tak memakainya.
 */
class DesaContext
{
    private const KEY = 'managed_desa_id';

    public static function set(int $desaId): void
    {
        session()->put(self::KEY, $desaId);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    public static function id(): ?int
    {
        $id = session(self::KEY);

        return $id ? (int) $id : null;
    }

    public static function desa(): ?Desa
    {
        $id = self::id();

        return $id ? Desa::find($id) : null;
    }
}
