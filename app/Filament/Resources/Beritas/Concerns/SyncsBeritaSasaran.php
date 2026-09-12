<?php

namespace App\Filament\Resources\Beritas\Concerns;

use App\Models\Berita;
use App\Models\Nagari;

/**
 * Sinkronisasi sasaran nagari (audiens) untuk Berita & Pengumuman.
 * Dua mode:
 * - semua_nagari (DINAMIS, pivot berita_nagari dikosongkan, seluruh nagari melihatnya).
 * - sasaran nagari tertentu (pivot berita_nagari dan/atau nagari_id).
 * Khusus operator: dipaksa ke nagarinya sendiri (semua_nagari=false, nagari_id=operator->nagari_id).
 *
 * @property Berita $record
 * @property array<string, mixed> $data
 */
trait SyncsBeritaSasaran
{
    protected function syncSasaran(): void
    {
        $actor = auth()->user();

        if ($actor?->isOperator()) {
            $this->record->update([
                'semua_nagari' => false,
                'nagari_id' => $actor->nagari_id,
            ]);
            $this->record->nagaris()->detach();

            return;
        }

        // Jika semua_nagari diaktifkan oleh Superadmin: pivot dikosongkan, audiens dinamis mencakup semua.
        if ($this->record->semua_nagari) {
            $this->record->update(['nagari_id' => null]);
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
            $this->record->update([
                'semua_nagari' => true,
                'nagari_id' => null,
            ]);
            $this->record->nagaris()->detach();

            return;
        }

        if (count($ids) === 1) {
            $this->record->update(['nagari_id' => $ids[0]]);
            $this->record->nagaris()->sync($ids);
        } else {
            $this->record->update(['nagari_id' => null]);
            $this->record->nagaris()->sync($ids);
        }
    }

    /**
     * Isi field form sasaran dari pivot/nagari_id saat membuka Edit.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateSasaran(array $data): array
    {
        if (auth()->user()?->isOperator()) {
            return $data;
        }

        if ($this->record->nagaris()->exists()) {
            $data['sasaran'] = $this->record->nagaris()->pluck('nagaris.id')->all();
        } elseif ($this->record->nagari_id) {
            $data['sasaran'] = [$this->record->nagari_id];
        } else {
            $data['sasaran'] = [];
        }

        return $data;
    }
}
