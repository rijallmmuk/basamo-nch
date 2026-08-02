<?php

declare(strict_types=1);

namespace App\Services\Sdg;

use App\Models\SdgAchievement;
use App\Models\SdgGoal;
use App\Models\SdgIndicator;
use App\Models\SdgPillar;
use App\Models\SdgTarget;
use Illuminate\Support\Collection;

/**
 * Perhitungan capaian SDGs Desa. Kosakata: per-poin = NILAI, agregat nagari = SKOR.
 *
 * Aturan:
 * - SEMUA 18 poin dihitung untuk semua nagari — TAK ADA konsep relevansi/N-A
 *   (mengikuti praktik nyata penilaian SDGs Desa).
 * - Poin tanpa capaian / belum diisi = 0 (aturan tegas), tetap masuk penyebut.
 * - Semua nilai di-clamp 0–100 — data korup tak pernah keluar rentang.
 */
class SdgScoringService
{
    /** NILAI sebuah capaian Poin: persentase ter-clamp 0–100 (kosong = 0). */
    public function nilaiPoin(SdgAchievement $achievement): float
    {
        return $achievement->persentase !== null
            ? max(0.0, min(100.0, (float) $achievement->persentase))
            : 0.0;
    }

    /**
     * Capaian 18 Poin untuk satu nagari (potret berjalan, tanpa dimensi tahun).
     *
     * @return Collection<int, array{goal: SdgGoal, achievement: SdgAchievement|null, nilai: float, terisi: bool}>
     *                                                                                                             di-key oleh nomor Poin (1..18).
     */
    public function capaianPoin(int $nagariId): Collection
    {
        $achievements = SdgAchievement::query()
            ->where('nagari_id', $nagariId)
            ->get()
            ->keyBy('sdg_goal_id');

        return SdgGoal::query()->with('pillar')->orderBy('nomor')->get()->mapWithKeys(function (SdgGoal $goal) use ($achievements): array {
            $a = $achievements->get($goal->id);

            return [$goal->nomor => [
                'goal' => $goal,
                'achievement' => $a,
                'nilai' => $a ? $this->nilaiPoin($a) : 0.0,      // tanpa capaian = 0
                'terisi' => (bool) $a,
            ]];
        });
    }

    /** SKOR Nagari (total keseluruhan) = rata-rata nilai SEMUA poin (0–100). */
    public function skorNagari(int $nagariId): float
    {
        $poin = $this->capaianPoin($nagariId);

        if ($poin->isEmpty()) {
            return 0.0; // referensi belum ter-seed
        }

        return round($poin->sum(fn (array $p): float => $p['nilai']) / $poin->count(), 2);
    }

    /**
     * Capaian 18 Poin DIKELOMPOKKAN per Pilar, untuk halaman Teras Nagari.
     *
     * Sasaran dan indikator dimuat HANYA untuk poin yang sedang dibuka, bukan
     * untuk kedelapan belas sekaligus. Referensinya berisi 214 sasaran dan 386
     * indikator; merendernya serentak membengkakkan halaman dari 162 KB menjadi
     * 756 KB, dan ini halaman publik yang banyak dibuka dari ponsel. Poin lain
     * tetap menyebut berapa sasaran dan indikator yang dimilikinya, dihitung
     * lewat dua kueri agregat yang murah.
     *
     * @return Collection<int, array{pilar: SdgPillar, skor: float, poin: Collection<int, array<string, mixed>>}>
     */
    public function capaianPerPilar(int $nagariId, ?int $poinTerbuka = null): Collection
    {
        $jumlahSasaran = SdgTarget::query()
            ->selectRaw('sdg_goal_id, COUNT(*) AS jumlah')
            ->groupBy('sdg_goal_id')
            ->pluck('jumlah', 'sdg_goal_id');

        $jumlahIndikator = SdgIndicator::query()
            ->join('sdg_targets', 'sdg_targets.id', '=', 'sdg_indicators.sdg_target_id')
            ->selectRaw('sdg_targets.sdg_goal_id AS goal_id, COUNT(*) AS jumlah')
            ->groupBy('sdg_targets.sdg_goal_id')
            ->pluck('jumlah', 'goal_id');

        $panduan = $poinTerbuka === null
            ? null
            : SdgGoal::query()->with('targets.indicators')->where('nomor', $poinTerbuka)->first();

        return $this->capaianPoin($nagariId)
            ->map(function (array $poin, int $nomor) use ($panduan, $poinTerbuka, $jumlahSasaran, $jumlahIndikator): array {
                $goal = $poin['goal'];

                return [
                    ...$poin,
                    // Poin yang sedang dibuka memakai model yang sudah membawa
                    // sasaran beserta indikatornya.
                    'goal' => $nomor === $poinTerbuka && $panduan ? $panduan : $goal,
                    'terbuka' => $nomor === $poinTerbuka,
                    'jumlah_sasaran' => (int) ($jumlahSasaran[$goal->getKey()] ?? 0),
                    'jumlah_indikator' => (int) ($jumlahIndikator[$goal->getKey()] ?? 0),
                ];
            })
            // preserveKeys WAJIB: bawaan `groupBy` membuang kunci aslinya, dan kunci
            // itulah nomor poin SDGs. Tanpa ini nomornya berubah jadi urutan dalam
            // kelompok, sehingga poin 1 sampai 5 tampil sebagai 0 sampai 4.
            ->groupBy(fn (array $poin): int => (int) $poin['goal']->sdg_pillar_id, preserveKeys: true)
            ->map(fn (Collection $poin): array => [
                'pilar' => $poin->first()['goal']->pillar,
                'skor' => round($poin->sum(fn (array $p): float => $p['nilai']) / $poin->count(), 2),
                'poin' => $poin,
            ])
            ->values();
    }

    /** Kelengkapan pendataan: jumlah Poin yang sudah terisi capaian / 18. */
    public function kelengkapan(int $nagariId): array
    {
        $poin = $this->capaianPoin($nagariId);
        $terisi = $poin->filter(fn (array $p): bool => $p['terisi'])->count();

        return [
            'terisi' => $terisi,
            'total' => $poin->count(),
            'persen' => $poin->isEmpty() ? 0.0 : round($terisi / $poin->count() * 100, 1),
        ];
    }
}
