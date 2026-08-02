<?php

namespace App\Services;

use App\Models\Evaluasi;
use App\Models\EvaluasiPertanyaan;
use App\Models\Materi;
use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SlcBatchAuthoringService
{
    /**
     * @param  array<int, array<string, mixed>>  $materis
     * @return Collection<int, Materi>
     */
    public function createMateris(Module $module, User $actor, array $materis): Collection
    {
        Gate::forUser($actor)->authorize('update', $module);

        if ($module->trashed()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($module, $materis): Collection {
            $existingIds = $module->materis()
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values();

            $submittedRawIds = collect($materis)
                ->pluck('id')
                ->filter()
                ->map(fn ($id): int => (int) $id);

            $submittedIds = $submittedRawIds
                ->unique()
                ->sort()
                ->values();

            if (
                $submittedRawIds->count() !== $submittedIds->count()
                || $submittedIds->all() !== $existingIds->all()
            ) {
                throw ValidationException::withMessages([
                    'data.materis' => 'Daftar materi telah berubah. Muat ulang halaman sebelum menyimpan.',
                ]);
            }

            return collect($materis)
                ->values()
                ->map(function (array $data, int $index) use ($module): Materi {
                    $values = [
                        'judul' => trim((string) ($data['judul'] ?? '')),
                        'blocks' => array_values($data['blocks'] ?? []),
                        'urutan' => $index + 1,
                    ];

                    if (filled($data['id'] ?? null)) {
                        $materi = $module->materis()->findOrFail((int) $data['id']);
                        $materi->update($values);

                        return $materi;
                    }

                    return $module->materis()->create($values);
                });
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $pertanyaans
     * @return Collection<int, EvaluasiPertanyaan>
     */
    public function createPertanyaans(Evaluasi $evaluasi, User $actor, array $pertanyaans): Collection
    {
        Gate::forUser($actor)->authorize('update', $evaluasi);

        if ($evaluasi->trashed()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($evaluasi, $pertanyaans): Collection {
            $existingIds = $evaluasi->pertanyaans()
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values();

            $submittedRawIds = collect($pertanyaans)
                ->pluck('id')
                ->filter()
                ->map(fn ($id): int => (int) $id);
            $submittedIds = $submittedRawIds->unique()->sort()->values();

            if (
                $submittedRawIds->count() !== $submittedIds->count()
                || $submittedIds->all() !== $existingIds->all()
            ) {
                throw ValidationException::withMessages([
                    'data.pertanyaans' => 'Daftar soal telah berubah. Muat ulang halaman sebelum menyimpan.',
                ]);
            }

            return collect($pertanyaans)
                ->values()
                ->map(function (array $data, int $index) use ($evaluasi): EvaluasiPertanyaan {
                    if (filled($data['id'] ?? null)) {
                        $pertanyaan = $evaluasi->pertanyaans()->findOrFail((int) $data['id']);
                        $pertanyaan->update([
                            'pertanyaan' => trim((string) ($data['pertanyaan'] ?? '')),
                            'urutan' => $index + 1,
                        ]);
                    } else {
                        $pertanyaan = $evaluasi->pertanyaans()->create([
                            'pertanyaan' => trim((string) ($data['pertanyaan'] ?? '')),
                            'urutan' => $index + 1,
                        ]);
                    }

                    $this->syncPertanyaanOpsis($pertanyaan, $data['opsis'] ?? []);

                    return $pertanyaan;
                });
        });
    }

    /** @param array<int, array<string, mixed>> $opsis */
    private function syncPertanyaanOpsis(EvaluasiPertanyaan $pertanyaan, array $opsis): void
    {
        $existingIds = $pertanyaan->opsis()
            ->lockForUpdate()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
        $submittedRawIds = collect($opsis)
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id);
        $submittedIds = $submittedRawIds->unique()->values();

        if (
            $submittedRawIds->count() !== $submittedIds->count()
            || $submittedIds->diff($existingIds)->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'data.pertanyaans' => 'Pilihan jawaban telah berubah. Muat ulang halaman sebelum menyimpan.',
            ]);
        }

        $pertanyaan->opsis()->whereNotIn('id', $submittedIds)->delete();

        foreach (array_values($opsis) as $index => $data) {
            $values = [
                'teks_opsi' => trim((string) ($data['teks_opsi'] ?? '')),
                'is_correct' => (bool) ($data['is_correct'] ?? false),
                'urutan' => $index + 1,
            ];

            if (filled($data['id'] ?? null)) {
                $pertanyaan->opsis()->findOrFail((int) $data['id'])->update($values);
            } else {
                $pertanyaan->opsis()->create($values);
            }
        }
    }

}
