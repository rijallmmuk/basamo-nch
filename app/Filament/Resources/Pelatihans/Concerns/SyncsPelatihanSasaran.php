<?php

namespace App\Filament\Resources\Pelatihans\Concerns;

use App\Models\Nagari;
use App\Models\Pelatihan;

/**
 * Sinkronisasi sasaran nagari (audiens). Dua mode: `semua_nagari` (DINAMIS, pivot
 * dikosongkan, nagari baru otomatis ikut) atau pivot `pelatihan_nagari` (nagari
 * tertentu dari Repeater). Operator: dipaksa ke nagarinya sendiri (semua_nagari=false).
 * Status pelaksanaan TIDAK diatur di sini, ada aksi terpisah (lihat PelatihanResource::setStatus).
 *
 * @property Pelatihan $record
 * @property array<string, mixed> $data
 */
trait SyncsPelatihanSasaran
{
    protected function syncSasaran(): void
    {
        $actor = auth()->user();

        if ($actor?->isOperator()) {
            $this->record->update(['semua_nagari' => false]);
            $this->record->nagaris()->sync($actor->nagari_id === null ? [] : [$actor->nagari_id]);

            return;
        }

        // semua_nagari (kolom) sudah tersimpan lewat form; audiens dinamis → pivot kosong.
        if ($this->record->semua_nagari) {
            $this->record->nagaris()->detach();

            return;
        }

        $rawSasaran = $this->data['sasaran'] ?? [];
        $ids = collect(is_array($rawSasaran) ? $rawSasaran : [])
            ->flatMap(fn ($item) => is_array($item) ? [$item['nagari_id'] ?? null] : [$item])
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $totalNagarisCount = Nagari::query()->count();
        if ($totalNagarisCount > 0 && count($ids) >= $totalNagarisCount) {
            $this->record->update(['semua_nagari' => true]);
            $this->record->nagaris()->detach();

            return;
        }

        $this->record->nagaris()->sync($ids);
    }

    /**
     * Isi field form sasaran dari pivot saat membuka Edit.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateSasaran(array $data): array
    {
        if (auth()->user()?->isOperator()) {
            return $data; // operator tak punya field sasaran (dipaksa ke nagarinya)
        }

        $data['sasaran'] = $this->record->nagaris()->pluck('nagaris.id')->all();

        return $data;
    }
}
