<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\Nagari;
use App\Models\Pelatihan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicSlcCatalogService
{
    /**
     * @param  array<string, mixed>  $rawFilters
     * @return array{
     *   pelatihans: LengthAwarePaginator,
     *   nagariOptions: Collection<int, Nagari>,
     *   selectedNagari: Nagari|null,
     *   filters: array{q: string, nagari: string}
     * }
     */
    public function catalog(?Nagari $tenant, array $rawFilters): array
    {
        $filters = [
            'q' => trim((string) ($rawFilters['q'] ?? '')),
            'nagari' => trim((string) ($rawFilters['nagari'] ?? '')),
        ];

        $selectedNagari = $tenant ?? $this->selectedNagari($filters['nagari']);

        // Kedua cabang wajib `ready()`: pelatihan yang belum berisi materi tidak
        // pernah tampil ke publik, baik di katalog global maupun halaman nagari.
        $pelatihanBase = Pelatihan::query()
            ->ready()
            ->when(
                $selectedNagari,
                fn (Builder $query) => $query->forNagari($selectedNagari->getKey()),
                fn (Builder $query) => $query->withPublicAudience(),
            );

        $pelatihans = (clone $pelatihanBase)
            ->when($filters['q'] !== '', fn (Builder $query) => $query
                ->where(function (Builder $search) use ($filters): void {
                    $search
                        ->where('deskripsi', 'like', '%'.$filters['q'].'%')
                        ->orWhereHas('tema', fn (Builder $tema) => $tema
                            ->where('nama', 'like', '%'.$filters['q'].'%'))
                        // Modul tetap ditemukan melalui pelatihan induknya, bukan
                        // dipisahkan menjadi katalog kedua yang kehilangan konteks.
                        ->orWhereHas('modules', fn (Builder $modules) => $modules
                            ->ready()
                            ->where(fn (Builder $moduleSearch) => $moduleSearch
                                ->where('judul', 'like', '%'.$filters['q'].'%')
                                ->orWhere('deskripsi', 'like', '%'.$filters['q'].'%')));
                }))
            ->with(['tema', 'creator:id,name,lembaga,nagari_id', 'creator.roles', 'creator.nagari:id,nama', 'pengajars:id,name,lembaga,nagari_id', 'pengajars.roles', 'pengajars.nagari:id,nama'])
            ->withCount(['modules' => fn (Builder $modules) => $modules
                ->ready()])
            ->latest()
            ->paginate(12, pageName: 'pelatihan_page')
            ->withQueryString();

        return [
            'pelatihans' => $pelatihans,
            'nagariOptions' => Nagari::query()
                ->where('status', ActiveStatus::Active)
                ->orderBy('nama')
                ->get(['id', 'nama', 'slug', 'kabupaten']),
            'selectedNagari' => $selectedNagari,
            'filters' => $filters,
        ];
    }

    private function selectedNagari(string $nagariId): ?Nagari
    {
        if (! ctype_digit($nagariId)) {
            return null;
        }

        return Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->find((int) $nagariId);
    }
}
