<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Aturan authoring modul. Setiap modul WAJIB bernaung pada satu pelaksanaan
 * pelatihan; tidak ada lagi modul mandiri. Sasaran nagari selalu diwarisi dari
 * pelaksanaan agar hanya ada satu sumber kebenaran.
 */
class SlcModuleService
{
    /**
     * Normalisasi data authoring dari form yang tidak dipercaya dan pastikan aktor
     * berhak mengelola pelaksanaan terpilih.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function authoringData(array $data, User $actor, ?Module $module = null): array
    {
        $pelatihan = Pelatihan::query()
            ->manageableBy($actor)
            ->find($data['pelatihan_id'] ?? null);

        if (! $pelatihan) {
            throw ValidationException::withMessages([
                'pelatihan_id' => 'Pelatihan tidak valid atau berada di luar kewenangan Anda.',
            ]);
        }

        Gate::forUser($actor)->authorize('kelolaKonten', $pelatihan);

        $data['pelatihan_id'] = $pelatihan->getKey();
        $data['created_by'] = $module?->created_by ?? $actor->getKey();

        $this->validatePrerequisite(
            filled($data['prasyarat_module_id'] ?? null)
                ? (int) $data['prasyarat_module_id']
                : null,
            $pelatihan,
            $actor,
            $module,
        );

        unset($data['estimasi_menit']);

        return $data;
    }

    private function validatePrerequisite(
        ?int $prerequisiteId,
        Pelatihan $pelatihan,
        User $actor,
        ?Module $module,
    ): void {
        if ($prerequisiteId === null) {
            $this->validateDependents($module, $pelatihan);

            return;
        }

        $prerequisite = Module::query()
            ->manageableBy($actor)
            ->find($prerequisiteId);

        if (! $prerequisite || $prerequisite->getKey() === $module?->getKey()) {
            throw ValidationException::withMessages([
                'prasyarat_module_id' => 'Modul prasyarat tidak valid atau berada di luar kewenangan Anda.',
            ]);
        }

        if ($prerequisite->pelatihan_id !== $pelatihan->getKey()) {
            throw ValidationException::withMessages([
                'prasyarat_module_id' => 'Prasyarat harus modul lain dalam pelatihan yang sama.',
            ]);
        }

        $cursor = $prerequisite;
        $visited = [];

        while ($cursor->prasyarat_module_id !== null) {
            if (isset($visited[$cursor->getKey()])) {
                throw ValidationException::withMessages([
                    'prasyarat_module_id' => 'Rantai prasyarat yang dipilih sudah mengandung siklus.',
                ]);
            }

            $visited[$cursor->getKey()] = true;

            if ($cursor->prasyarat_module_id === $module?->getKey()) {
                throw ValidationException::withMessages([
                    'prasyarat_module_id' => 'Prasyarat ini membentuk siklus antar-modul.',
                ]);
            }

            $cursor = Module::query()->find($cursor->prasyarat_module_id);

            if (! $cursor) {
                break;
            }
        }

        $this->validateDependents($module, $pelatihan);
    }

    /**
     * Memindahkan modul ke pelaksanaan lain tidak boleh meninggalkan modul dependen
     * dengan prasyarat di luar pelaksanaannya.
     */
    private function validateDependents(?Module $module, Pelatihan $pelatihan): void
    {
        if (! $module) {
            return;
        }

        $adaDependenDiLuar = Module::query()
            ->where('prasyarat_module_id', $module->getKey())
            ->where('pelatihan_id', '!=', $pelatihan->getKey())
            ->exists();

        if ($adaDependenDiLuar) {
            throw ValidationException::withMessages([
                'pelatihan_id' => 'Modul masih menjadi prasyarat modul pada pelatihan lain.',
            ]);
        }
    }
}
