<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use App\Services\PendudukService;

/**
 * Form UserResource terpadu: field identitas (penduduk) tampil bersama field akun,
 * tapi disimpan ke tabel `penduduk`. Trait ini memisahkan keduanya saat simpan dan
 * memuat identitas saat edit.
 */
trait InteractsWithPenduduk
{
    /** @var array<string, mixed> */
    protected array $pendudukData = [];

    /**
     * Keluarkan field identitas dari data akun agar tak ikut mass-assign ke `users`.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractPendudukData(array $data): array
    {
        foreach (PendudukService::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $this->pendudukData[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        return $data;
    }

    /** Upsert penduduk untuk akun warga (dipanggil setelah record akun tersimpan). */
    protected function syncPenduduk(): void
    {
        if (! $this->record->isPortalAccount()) {
            return;
        }

        app(PendudukService::class)->syncForUser($this->record->refresh(), $this->pendudukData);
    }
}
