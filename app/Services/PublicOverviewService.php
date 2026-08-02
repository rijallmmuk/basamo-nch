<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Models\EvaluasiPercobaan;
use App\Models\IdmStatus;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\Penduduk;
use App\Models\SdgAchievement;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\UserModuleProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class PublicOverviewService
{
    /**
     * Ringkasan Teras Nagari. Seluruh nilai berupa agregat; tidak ada identitas,
     * NIK, alamat, tanggal lahir, atau record penduduk yang dikirim ke view.
     *
     * @return array<string, mixed>
     */
    public function overview(?Nagari $nagari = null): array
    {
        $cacheKey = 'public.teras.v2.'.($nagari?->getKey() ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(15), fn (): array => [
            'metrics' => $this->metrics($nagari),
            'gender' => $this->gender($nagari),
            'ageGroups' => $this->ageGroups($nagari),
            'education' => $this->lookupDistribution($nagari, 'pendidikan', 'pendidikan_id'),
            'occupations' => $this->lookupDistribution($nagari, 'pekerjaan', 'pekerjaan_id'),
            'learning' => $this->learning($nagari),
            'economy' => $this->economy($nagari),
            'sdgs' => $this->sdgs($nagari),
            'idm' => $this->idm($nagari),
        ]);
    }

    /** @return list<array{label: string, value: int, icon: string, description: string}> */
    private function metrics(?Nagari $nagari): array
    {
        $metrics = [];

        if ($nagari === null) {
            $metrics[] = [
                'label' => 'Nagari Aktif',
                'value' => Nagari::query()->where('status', ActiveStatus::Active)->count(),
                'icon' => 'heroicon-o-map-pin',
                'description' => 'Mitra dalam ekosistem BASAMO NCH',
            ];
        }

        return [
            ...$metrics,
            ['label' => 'Penduduk', 'value' => $this->pendudukQuery($nagari)->count(), 'icon' => 'heroicon-o-user-group', 'description' => 'Data agregat SID'],
            ['label' => 'Pelatihan', 'value' => $this->pelatihanQuery($nagari)->count(), 'icon' => 'heroicon-o-academic-cap', 'description' => 'Pelatihan siap dipelajari'],
            ['label' => 'Modul', 'value' => $this->moduleQuery($nagari)->count(), 'icon' => 'heroicon-o-book-open', 'description' => 'Modul terbit'],
            ['label' => 'UMKM', 'value' => $this->umkmQuery($nagari)->count(), 'icon' => 'heroicon-o-building-storefront', 'description' => 'Rumah usaha aktif'],
            ['label' => 'Produk', 'value' => $this->productQuery($nagari)->count(), 'icon' => 'heroicon-o-shopping-bag', 'description' => 'Produk dipublikasikan'],
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function gender(?Nagari $nagari): array
    {
        $row = $this->pendudukQuery($nagari)
            ->selectRaw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) AS laki_laki")
            ->selectRaw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) AS perempuan")
            ->selectRaw('SUM(CASE WHEN jenis_kelamin IS NULL THEN 1 ELSE 0 END) AS belum_terdata')
            ->first();

        return [
            ['label' => 'Laki-laki', 'value' => (int) ($row?->laki_laki ?? 0)],
            ['label' => 'Perempuan', 'value' => (int) ($row?->perempuan ?? 0)],
            ['label' => 'Belum terdata', 'value' => (int) ($row?->belum_terdata ?? 0)],
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function ageGroups(?Nagari $nagari): array
    {
        $today = now()->startOfDay();
        $age18 = $today->copy()->subYears(18)->toDateString();
        $age26 = $today->copy()->subYears(26)->toDateString();
        $age41 = $today->copy()->subYears(41)->toDateString();
        $age61 = $today->copy()->subYears(61)->toDateString();

        $row = $this->pendudukQuery($nagari)
            ->selectRaw('SUM(CASE WHEN tanggal_lahir > ? THEN 1 ELSE 0 END) AS usia_0_17', [$age18])
            ->selectRaw('SUM(CASE WHEN tanggal_lahir <= ? AND tanggal_lahir > ? THEN 1 ELSE 0 END) AS usia_18_25', [$age18, $age26])
            ->selectRaw('SUM(CASE WHEN tanggal_lahir <= ? AND tanggal_lahir > ? THEN 1 ELSE 0 END) AS usia_26_40', [$age26, $age41])
            ->selectRaw('SUM(CASE WHEN tanggal_lahir <= ? AND tanggal_lahir > ? THEN 1 ELSE 0 END) AS usia_41_60', [$age41, $age61])
            ->selectRaw('SUM(CASE WHEN tanggal_lahir <= ? THEN 1 ELSE 0 END) AS usia_61_plus', [$age61])
            ->first();

        return [
            ['label' => '0–17 tahun', 'value' => (int) ($row?->usia_0_17 ?? 0)],
            ['label' => '18–25 tahun', 'value' => (int) ($row?->usia_18_25 ?? 0)],
            ['label' => '26–40 tahun', 'value' => (int) ($row?->usia_26_40 ?? 0)],
            ['label' => '41–60 tahun', 'value' => (int) ($row?->usia_41_60 ?? 0)],
            ['label' => '61+ tahun', 'value' => (int) ($row?->usia_61_plus ?? 0)],
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function lookupDistribution(?Nagari $nagari, string $table, string $foreignKey): array
    {
        return $this->pendudukQuery($nagari)
            ->join($table, $table.'.id', '=', 'penduduk.'.$foreignKey)
            ->select($table.'.nama AS label')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy($table.'.id', $table.'.nama')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn (Penduduk $row): array => [
                'label' => (string) $row->getAttribute('label'),
                'value' => (int) $row->getAttribute('total'),
            ])
            ->all();
    }

    /** @return array<string, int> */
    private function learning(?Nagari $nagari): array
    {
        $completed = UserModuleProgress::query()
            ->where('user_module_progress.status', ModuleProgressStatus::Completed)
            ->whereHas('user', fn (Builder $query) => $query
                ->whereIn('nagari_id', $this->activeNagariIds($nagari)))
            ->count();

        $attempts = EvaluasiPercobaan::query()
            ->whereHas('user', fn (Builder $query) => $query
                ->whereIn('nagari_id', $this->activeNagariIds($nagari)))
            ->count();

        return [
            'pelatihans' => $this->pelatihanQuery($nagari)->count(),
            'modules' => $this->moduleQuery($nagari)->count(),
            'completed_modules' => $completed,
            'evaluasi_percobaans' => $attempts,
        ];
    }

    /** @return array<string, int> */
    private function economy(?Nagari $nagari): array
    {
        $profiles = $this->umkmQuery($nagari);
        $products = $this->productQuery($nagari);

        return [
            'profiles' => (clone $profiles)->count(),
            'products' => (clone $products)->count(),
            'product_views' => (int) (clone $products)->sum('jumlah_dilihat'),
            'qr_profiles' => (clone $profiles)
                ->whereHas('media', fn (Builder $query) => $query
                    ->where('collection_name', 'qr'))
                ->count(),
        ];
    }

    /** @return array{score: float, filled: int, total: int} */
    private function sdgs(?Nagari $nagari): array
    {
        $nagariCount = $nagari
            ? 1
            : Nagari::query()->where('status', ActiveStatus::Active)->count();
        $total = $nagariCount * 18;
        $query = SdgAchievement::query()
            ->whereHas('nagari', fn (Builder $nagaris) => $nagaris
                ->where('status', ActiveStatus::Active)
                ->when($nagari, fn (Builder $scope) => $scope->whereKey($nagari->getKey())));
        $filled = (clone $query)->whereNotNull('persentase')->count();
        $sum = (float) (clone $query)->sum('persentase');

        return [
            'score' => $total > 0 ? round(max(0, min($sum / $total, 100)), 1) : 0.0,
            'filled' => $filled,
            'total' => $total,
        ];
    }

    /** @return array<string, mixed> */
    private function idm(?Nagari $nagari): array
    {
        if ($nagari) {
            $latest = IdmStatus::query()
                ->where('nagari_id', $nagari->getKey())
                ->latest('tahun')
                ->first();

            return [
                'covered' => $latest ? 1 : 0,
                'latest' => $latest?->only([
                    'tahun', 'skor', 'status', 'skor_iks', 'skor_ike', 'skor_ikl',
                ]),
                'statuses' => $latest ? [$latest->status => 1] : [],
            ];
        }

        $latestYears = IdmStatus::query()
            ->select('nagari_id')
            ->selectRaw('MAX(tahun) AS tahun')
            ->whereHas('nagari', fn (Builder $query) => $query
                ->where('status', ActiveStatus::Active))
            ->groupBy('nagari_id');

        $statuses = IdmStatus::query()
            ->joinSub($latestYears, 'latest_idm', fn ($join) => $join
                ->on('idm_statuses.nagari_id', '=', 'latest_idm.nagari_id')
                ->on('idm_statuses.tahun', '=', 'latest_idm.tahun'))
            ->select('idm_statuses.status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('idm_statuses.status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();

        return [
            'covered' => array_sum($statuses),
            'latest' => null,
            'statuses' => $statuses,
        ];
    }

    /** @return Builder<Penduduk> */
    private function pendudukQuery(?Nagari $nagari): Builder
    {
        return Penduduk::query()
            ->whereHas('nagari', fn (Builder $query) => $query
                ->where('status', ActiveStatus::Active)
                ->when($nagari, fn (Builder $scope) => $scope->whereKey($nagari->getKey())));
    }

    /** @return Builder<Pelatihan> */
    private function pelatihanQuery(?Nagari $nagari): Builder
    {
        // `ready()` berlaku untuk kedua cabang: pelatihan tanpa materi tidak pernah
        // ikut terhitung/tampil di permukaan publik.
        return Pelatihan::query()
            ->ready()
            ->when(
                $nagari,
                fn (Builder $query) => $query->forNagari($nagari->getKey()),
                fn (Builder $query) => $query->withPublicAudience(),
            );
    }

    /** @return Builder<Module> */
    private function moduleQuery(?Nagari $nagari): Builder
    {
        return Module::query()
            ->when(
                $nagari,
                fn (Builder $query) => $query
                    ->ready()
                    ->dariPelatihanAktif()
                    ->forNagari($nagari->getKey()),
                fn (Builder $query) => $query->publiclyVisible(),
            );
    }

    /** @return Builder<UmkmProfile> */
    private function umkmQuery(?Nagari $nagari): Builder
    {
        return UmkmProfile::query()
            ->where('status', ActiveStatus::Active)
            ->whereHas('nagari', fn (Builder $query) => $query
                ->where('status', ActiveStatus::Active)
                ->when($nagari, fn (Builder $scope) => $scope->whereKey($nagari->getKey())));
    }

    /** @return Builder<UmkmProduct> */
    private function productQuery(?Nagari $nagari): Builder
    {
        return UmkmProduct::query()
            ->whereHas('umkmProfile', fn (Builder $profiles) => $profiles
                ->where('status', ActiveStatus::Active)
                ->whereHas('nagari', fn (Builder $nagaris) => $nagaris
                    ->where('status', ActiveStatus::Active)
                    ->when($nagari, fn (Builder $scope) => $scope->whereKey($nagari->getKey()))));
    }

    /** @return Builder<Nagari> */
    private function activeNagariIds(?Nagari $nagari): Builder
    {
        return Nagari::query()
            ->select('id')
            ->where('status', ActiveStatus::Active)
            ->when($nagari, fn (Builder $query) => $query->whereKey($nagari->getKey()));
    }
}
