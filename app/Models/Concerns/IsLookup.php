<?php

namespace App\Models\Concerns;

/**
 * Perilaku bersama tabel data master/referensi (agama, status_perkawinan,
 * pekerjaan, jenis_desa, jenis_sub_unit): opsi dropdown = baris aktif saja,
 * urut `urutan`. `$includeId` menyertakan nilai terpilih walau sudah nonaktif —
 * record lama tetap bisa dibuka & disimpan tanpa kehilangan nilainya.
 */
trait IsLookup
{
    /** @return array<int, string> */
    public static function options(?int $includeId = null): array
    {
        return static::query()
            ->where(fn ($q) => $q->where('aktif', true)
                ->when($includeId, fn ($q2) => $q2->orWhere('id', $includeId)))
            ->orderBy('urutan')
            ->pluck('nama', 'id')
            ->all();
    }
}
