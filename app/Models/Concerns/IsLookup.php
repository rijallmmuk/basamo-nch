<?php

namespace App\Models\Concerns;

/**
 * Perilaku bersama tabel data master/referensi (agama, status_perkawinan,
 * pekerjaan): opsi dropdown = baris aktif saja.
 *
 * Isinya himpunan baku yang di-seed sekali di migrasi dan praktis tak pernah
 * berubah, jadi tidak ada kolom urutan: id-nya sudah lahir dalam urutan resmi
 * (diselaraskan dengan OpenSID) dan itu yang dipakai. `$includeId` menyertakan
 * nilai terpilih walau sudah nonaktif, supaya record lama tetap bisa dibuka dan
 * disimpan tanpa kehilangan nilainya.
 */
trait IsLookup
{
    /** @return array<int, string> */
    public static function options(?int $includeId = null): array
    {
        return static::query()
            ->where(fn ($q) => $q->where('aktif', true)
                ->when($includeId, fn ($q2) => $q2->orWhere('id', $includeId)))
            ->orderBy('id')
            ->pluck('nama', 'id')
            ->all();
    }
}
