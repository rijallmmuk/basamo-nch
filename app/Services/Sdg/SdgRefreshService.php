<?php

declare(strict_types=1);

namespace App\Services\Sdg;

use App\Models\Nagari;
use App\Models\RefWilayah;
use App\Models\SdgAchievement;
use App\Models\SdgGoal;
use Carbon\CarbonImmutable;

/**
 * Orkestrasi pengambilan skor SDGs satu nagari dari Kemendesa lalu menyimpannya ke
 * `sdg_achievements` (idempoten). SATU jalur kode yang dipakai bersama oleh command
 * terjadwal, hook create nagari, dan tombol "Perbarui" — agar perilaku selalu identik.
 *
 * Pemetaan kode: `nagari.wilayah_kode` → `ref_wilayah.kode_bps` → parameter API.
 */
class SdgRefreshService
{
    public function __construct(private readonly SdgKemendesaService $kemendesa) {}

    /**
     * Ambil & simpan skor 18 poin untuk satu nagari. Tak pernah melempar exception —
     * kegagalan dikembalikan sebagai status agar pemanggil (mis. create nagari) aman.
     *
     * @param Nagari $nagari Nagari sasaran yang akan disimpan capaiannya
     * @param string|null $customKodeBps Kode BPS alternatif/rujukan (misal untuk nagari pemekaran)
     * @return array{status: 'ok'|'gagal'|'tanpa_bps', average?: float, kode_bps?: string}
     */
    public function refreshNagari(Nagari $nagari, ?string $customKodeBps = null): array
    {
        $kodeBps = filled($customKodeBps) ? trim((string) $customKodeBps) : (
            $nagari->wilayah_kode
                ? RefWilayah::query()->where('kode', $nagari->wilayah_kode)->value('kode_bps')
                : null
        );

        if (! $kodeBps) {
            return ['status' => 'tanpa_bps'];
        }

        $hasil = $this->kemendesa->fetch($kodeBps);

        if ($hasil === null) {
            return ['status' => 'gagal', 'kode_bps' => $kodeBps];
        }

        $goalIdByNomor = SdgGoal::query()->pluck('id', 'nomor');

        try {
            foreach ($hasil['goals'] as $nomor => $skor) {
                $sdgGoalId = $goalIdByNomor[$nomor] ?? null;

                if (! $sdgGoalId) {
                    continue;
                }

                SdgAchievement::updateOrCreate(
                    ['nagari_id' => $nagari->getKey(), 'sdg_goal_id' => $sdgGoalId],
                    ['persentase' => $skor, 'fetched_at' => now()],
                );
            }
        } catch (\Throwable $e) {
            // Menepati kontrak "tak pernah throw": kegagalan DB tak boleh menggagalkan
            // batch command / aksi tombol — skor lama tetap tersimpan, dicoba lagi nanti.
            report($e);

            return ['status' => 'gagal', 'kode_bps' => $kodeBps];
        }

        return ['status' => 'ok', 'average' => $hasil['average'], 'kode_bps' => $kodeBps];
    }

    /** Waktu pengambilan terakhir untuk nagari (null bila belum pernah ditarik). */
    public function lastFetchedAt(Nagari $nagari): ?CarbonImmutable
    {
        $max = SdgAchievement::query()
            ->where('nagari_id', $nagari->getKey())
            ->max('fetched_at');

        return $max ? CarbonImmutable::parse($max) : null;
    }

    /**
     * Apakah nagari masih dalam masa cooldown (baru saja diambil)? Dipakai tombol
     * "Perbarui" agar tak menghajar endpoint tak resmi untuk data yang jarang berubah.
     */
    public function withinCooldown(Nagari $nagari): bool
    {
        $last = $this->lastFetchedAt($nagari);

        if ($last === null) {
            return false;
        }

        return $last->greaterThan(now()->subDays($this->cooldownDays()));
    }

    public function cooldownDays(): int
    {
        return max(0, (int) config('sdgs.refresh_cooldown_days', 7));
    }
}
