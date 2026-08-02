<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Models\EvaluasiPercobaan;
use App\Models\IdmStatus;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\SdgAchievement;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\UserModuleProgress;
use App\Support\Dashboard\DemografiData;
use Illuminate\Database\Eloquent\Builder;

class PublicOverviewService
{
    /**
     * Ringkasan Teras Nagari. Seluruh nilai berupa agregat; tidak ada identitas,
     * NIK, alamat, tanggal lahir, atau record penduduk yang dikirim ke view.
     *
     * @return array<string, mixed>
     */
    public function overview(?Nagari $nagari = null, bool $lengkap = true): array
    {
        $hanyaNagariAktif = $nagari === null;
        $demografi = DemografiData::ringkasan($nagari?->getKey(), $hanyaNagariAktif);

        $hasil = [
            'metrics' => $this->metrics($nagari, $demografi),
            'sdgs' => $this->sdgs($nagari),
            'idm' => $this->idm($nagari),
        ];

        // Beranda hanya memakai kartu utama, SDGs, dan IDM. Distribusi demografi,
        // belajar, serta ekonomi dihitung hanya untuk Teras agar halaman masuk
        // tetap ringan pada nagari dengan puluhan ribu penduduk.
        if (! $lengkap) {
            return $hasil;
        }

        $kelompokUmur = DemografiData::kelompokUmur($nagari?->getKey(), $hanyaNagariAktif);
        $kelompokUmur->put(
            'Belum terdata',
            DemografiData::tanpaTanggalLahir($nagari?->getKey(), $hanyaNagariAktif),
        );

        // Statistik publik sengaja dihitung dari sumber yang sama dengan dashboard,
        // tanpa cache snapshot. Impor warga memakai bulk insert (melewati event
        // model), sehingga cache 15 menit sebelumnya dapat mempertahankan angka 0
        // ketika dashboard sudah membaca ribuan penduduk yang baru masuk.
        return [
            ...$hasil,
            'gender' => $this->gender($demografi),
            'ageGroups' => $this->distribution($kelompokUmur),
            'education' => $this->distribution(DemografiData::distribusiPendidikan($nagari?->getKey(), $hanyaNagariAktif)),
            'occupations' => $this->distribution(DemografiData::distribusiPekerjaan($nagari?->getKey(), $hanyaNagariAktif)),
            'learning' => $this->learning($nagari),
            'economy' => $this->economy($nagari),
        ];
    }

    /** @return list<array{label: string, value: int, icon: string, description: string}> */
    private function metrics(?Nagari $nagari, array $demografi): array
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
            ['label' => 'Penduduk', 'value' => $demografi['penduduk'], 'icon' => 'heroicon-o-user-group', 'description' => number_format($demografi['akun_portal'], 0, ',', '.').' memiliki akun portal'],
            ['label' => 'Pelatihan', 'value' => $this->pelatihanQuery($nagari)->count(), 'icon' => 'heroicon-o-academic-cap', 'description' => 'Pelatihan siap dipelajari'],
            ['label' => 'Modul', 'value' => $this->moduleQuery($nagari)->count(), 'icon' => 'heroicon-o-book-open', 'description' => 'Modul terbit'],
            ['label' => 'UMKM', 'value' => $this->umkmQuery($nagari)->count(), 'icon' => 'heroicon-o-building-storefront', 'description' => 'Rumah usaha aktif'],
            ['label' => 'Produk', 'value' => $this->productQuery($nagari)->count(), 'icon' => 'heroicon-o-shopping-bag', 'description' => 'Produk dipublikasikan'],
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function gender(array $demografi): array
    {
        return [
            ['label' => 'Laki-laki', 'value' => $demografi['laki_laki']],
            ['label' => 'Perempuan', 'value' => $demografi['perempuan']],
            ['label' => 'Belum terdata', 'value' => $demografi['belum_terdata']],
        ];
    }

    /** @return list<array{label: string, value: int}> */
    private function distribution(\Illuminate\Support\Collection $data): array
    {
        return $data
            ->map(fn (int $value, string $label): array => [
                'label' => $label,
                'value' => $value,
            ])
            ->values()
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
