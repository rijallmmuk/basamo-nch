<?php

declare(strict_types=1);

namespace App\Services\Idm;

use App\Models\IdmStatus;
use App\Models\Nagari;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Orkestrasi pengambilan status IDM satu nagari lalu menyimpannya ke `idm_statuses`
 * (idempoten per tahun). SATU jalur kode dipakai command, hook create nagari, dan tombol
 * "Perbarui" — agar perilaku identik.
 *
 * Kode = `nagari.wilayah_kode` (kepmendagri) langsung, TANPA mapping kode BPS.
 */
class IdmRefreshService
{
    public function __construct(private readonly IdmKemendesaService $kemendesa) {}

    /**
     * Ambil & simpan status IDM. Bila $tahun null, cari OTOMATIS tahun terbaru yang punya
     * data (mundur dari tahun kini sebanyak year_lookback) — endpoint hanya menyimpan
     * hingga beberapa tahun ke belakang. Tak pernah melempar exception.
     *
     * @return array{status: 'ok'|'gagal'|'tanpa_kode', tahun?: int, idm?: IdmStatus}
     */
    public function refreshNagari(Nagari $nagari, ?int $tahun = null): array
    {
        $kode = $nagari->wilayah_kode;

        if (! $kode) {
            return ['status' => 'tanpa_kode'];
        }

        $tahunDicoba = $tahun !== null ? [$tahun] : $this->kandidatTahun();

        foreach ($tahunDicoba as $th) {
            $hasil = $this->kemendesa->fetch($kode, $th);

            if ($hasil === null) {
                continue;
            }

            try {
                $idm = DB::transaction(function () use ($nagari, $hasil): IdmStatus {
                    $idm = IdmStatus::updateOrCreate(
                        ['nagari_id' => $nagari->getKey(), 'tahun' => $hasil['tahun']],
                        [
                            'skor' => $hasil['skor'],
                            'status' => $hasil['status'],
                            'target_status' => $hasil['target_status'],
                            'skor_minimal' => $hasil['skor_minimal'],
                            'penambahan' => $hasil['penambahan'],
                            'skor_iks' => $hasil['skor_iks'],
                            'skor_ike' => $hasil['skor_ike'],
                            'skor_ikl' => $hasil['skor_ikl'],
                            'fetched_at' => now(),
                        ],
                    );

                    // Ganti-total indikator (snapshot mengikuti API).
                    $idm->indicators()->delete();

                    if ($hasil['indikator'] !== []) {
                        $now = now();
                        $idm->indicators()->insert(array_map(fn (array $i): array => [
                            'idm_status_id' => $idm->getKey(),
                            'dimensi' => $i['dimensi'],
                            'nomor' => $i['nomor'],
                            'indikator' => $i['indikator'],
                            'skor' => $i['skor'],
                            'keterangan' => $i['keterangan'],
                            'kegiatan' => $i['kegiatan'],
                            'nilai' => $i['nilai'],
                            'pelaksana' => $i['pelaksana'] !== null ? json_encode($i['pelaksana']) : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], $hasil['indikator']));
                    }

                    return $idm;
                });
            } catch (\Throwable $e) {
                // Menepati kontrak "tak pernah throw": kegagalan DB (deadlock/koneksi/
                // constraint) → status gagal, JANGAN gagalkan batch command / aksi tombol.
                report($e);

                return ['status' => 'gagal'];
            }

            return ['status' => 'ok', 'tahun' => $hasil['tahun'], 'idm' => $idm];
        }

        return ['status' => 'gagal'];
    }

    /** Daftar tahun kandidat (terbaru dulu) untuk auto-discovery. */
    public function kandidatTahun(): array
    {
        $kini = (int) now()->year;
        $lookback = max(1, (int) config('idm.year_lookback', 4));

        return range($kini, $kini - $lookback + 1);
    }

    /** Status IDM tahun TERBARU yang tersimpan untuk nagari (untuk tampilan). */
    public function latestFor(Nagari $nagari): ?IdmStatus
    {
        return IdmStatus::query()
            ->where('nagari_id', $nagari->getKey())
            ->orderByDesc('tahun')
            ->first();
    }

    public function lastFetchedAt(Nagari $nagari): ?CarbonImmutable
    {
        $max = IdmStatus::query()->where('nagari_id', $nagari->getKey())->max('fetched_at');

        return $max ? CarbonImmutable::parse($max) : null;
    }

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
        return max(0, (int) config('idm.refresh_cooldown_days', 7));
    }
}
