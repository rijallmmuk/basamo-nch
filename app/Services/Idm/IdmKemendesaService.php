<?php

namespace App\Services\Idm;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Status IDM (Indeks Desa Membangun) per nagari per tahun dari endpoint terbuka Kemendesa.
 * Pola sama dengan SdgKemendesaService: tak pernah throw, validasi bentuk JSON eksplisit,
 * retry HANYA saat gangguan koneksi.
 *
 * Kode = kode kepmendagri (= `nagari.wilayah_kode`, dengan/atau tanpa titik — keduanya
 * diterima endpoint; TIDAK memakai kode BPS). Kode salah / tahun tanpa data → HTTP 400
 * `{"error":true,"message":"ID Desa tidak ditemukan"}`, jadi non-200 apa pun = gagal.
 *
 * `mapData.ROW` menyela indikator dengan baris SUBTOTAL dimensi (baris ber-`NO`=null,
 * INDIKATOR "IKS 2024"/"IKE 2024"/"IKL 2024"/"IDM 2024"/"STATUS IDM 2024"). Indikator
 * muncul SEBELUM baris subtotal dimensinya, jadi parser mem-buffer indikator lalu
 * mengalirkannya ke dimensi begitu subtotalnya tiba.
 */
class IdmKemendesaService
{
    private const BASE = 'https://idm.kemendesa.go.id/open/api/desa/rumusan';

    /** Kolom "yang dapat melaksanakan kegiatan" (level pemerintahan → instansi). */
    private const PELAKSANA_KEYS = ['PUSAT', 'PROV', 'KAB', 'DESA', 'CSR', 'LAINNYA'];

    /**
     * @return array{
     *   skor: float, status: string, target_status: ?string, skor_minimal: ?float,
     *   penambahan: ?float, tahun: int, skor_iks: ?float, skor_ike: ?float, skor_ikl: ?float,
     *   indikator: array<int, array{dimensi: string, nomor: int, indikator: string, skor: int, keterangan: ?string, kegiatan: ?string, nilai: ?float, pelaksana: array<string, string>}>
     * }|null
     */
    public function fetch(string $kodeDesa, int $tahun): ?array
    {
        try {
            // Lihat catatan pada SdgKemendesaService: connect ke Kemendesa memakan
            // ~10,3 detik, sehingga batas 10 detik selalu putus tepat sebelum
            // sambungannya jadi.
            $endpoint = self::BASE."/{$kodeDesa}/{$tahun}";
            $proxyUrl = env('KEMENDESA_PROXY_URL');
            
            if (!empty($proxyUrl)) {
                $urlToFetch = rtrim($proxyUrl, '/');
                $query = ['url' => $endpoint];
            } else {
                $urlToFetch = $endpoint;
                $query = [];
            }

            $response = Http::connectTimeout(30)
                ->timeout(45)
                ->retry(2, 300, fn (\Throwable $e): bool => $e instanceof ConnectionException)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->withoutVerifying()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withHeaders([
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'application/json',
                ])
                ->get($urlToFetch, $query);

            if (! $response->successful()) {
                if ($response->status() !== 400) {
                    Log::warning('IDM rumusan: respons tidak sukses', [
                        'kode' => $kodeDesa,
                        'tahun' => $tahun,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
                // 400 = kode tak dikenal ATAU tahun tanpa data — keduanya "tak ada".
                return null;
            }

            $body = $response->json();

            if (! is_array($body) || ($body['error'] ?? true) !== false) {
                return null;
            }

            $summary = $body['mapData']['SUMMARIES'] ?? null;

            if (! is_array($summary) || ! isset($summary['SKOR_SAAT_INI'], $summary['STATUS'])) {
                Log::warning('IDM rumusan: struktur SUMMARIES tak sesuai dugaan', ['kode' => $kodeDesa, 'tahun' => $tahun]);

                return null;
            }

            [$dimensi, $indikator] = $this->parseRows($body['mapData']['ROW'] ?? []);

            return [
                'skor' => (float) $summary['SKOR_SAAT_INI'],
                'status' => (string) $summary['STATUS'],
                'target_status' => isset($summary['TARGET_STATUS']) ? (string) $summary['TARGET_STATUS'] : null,
                'skor_minimal' => isset($summary['SKOR_MINIMAL']) ? (float) $summary['SKOR_MINIMAL'] : null,
                'penambahan' => isset($summary['PENAMBAHAN']) ? (float) $summary['PENAMBAHAN'] : null,
                'tahun' => (int) ($summary['TAHUN'] ?? $tahun),
                'skor_iks' => $dimensi['IKS'],
                'skor_ike' => $dimensi['IKE'],
                'skor_ikl' => $dimensi['IKL'],
                'indikator' => $indikator,
            ];
        } catch (\Throwable $e) {
            Log::warning('IDM rumusan: gagal diambil', ['kode' => $kodeDesa, 'tahun' => $tahun, 'pesan' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Pisahkan skor 3 dimensi + daftar indikator dari ROW. Indikator (ber-`NO` numerik)
     * di-buffer hingga baris subtotal dimensinya (IKS/IKE/IKL) tiba, lalu dialirkan
     * dengan label dimensi tersebut.
     *
     * @param  array<int, mixed>  $rows
     * @return array{0: array{IKS: ?float, IKE: ?float, IKL: ?float}, 1: array<int, array<string, mixed>>}
     */
    private function parseRows(array $rows): array
    {
        $dimensi = ['IKS' => null, 'IKE' => null, 'IKL' => null];
        $indikator = [];
        $buffer = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            // Baris subtotal dimensi: NO null + INDIKATOR "IKS/IKE/IKL ...".
            if (($row['NO'] ?? null) === null) {
                $kode = strtoupper(substr((string) ($row['INDIKATOR'] ?? ''), 0, 3));

                if (array_key_exists($kode, $dimensi)) {
                    $dimensi[$kode] = is_numeric($row['SKOR'] ?? null) ? (float) $row['SKOR'] : null;

                    foreach ($buffer as $b) {
                        $indikator[] = ['dimensi' => $kode] + $b;
                    }
                    $buffer = [];
                }

                continue;
            }

            $buffer[] = $this->normalizeIndikator($row);
        }

        // Indikator tersisa di buffer = tak ada baris subtotal dimensi setelahnya
        // (struktur API berubah). Jangan tebak dimensinya — buang + catat untuk audit.
        if ($buffer !== []) {
            Log::warning('IDM rumusan: '.count($buffer).' indikator tanpa subtotal dimensi (struktur berubah?)');
        }

        return [$dimensi, $indikator];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeIndikator(array $row): array
    {
        $kegiatan = trim((string) ($row['KEGIATAN'] ?? ''));

        $pelaksana = [];
        foreach (self::PELAKSANA_KEYS as $key) {
            $instansi = trim((string) ($row[$key] ?? ''));

            if ($instansi !== '') {
                $pelaksana[$key] = $instansi;
            }
        }

        return [
            'nomor' => (int) $row['NO'],
            'indikator' => (string) ($row['INDIKATOR'] ?? ''),
            'skor' => (int) ($row['SKOR'] ?? 0),
            'keterangan' => filled($row['KETERANGAN'] ?? null) ? (string) $row['KETERANGAN'] : null,
            'kegiatan' => ($kegiatan !== '' && $kegiatan !== '-') ? $kegiatan : null,
            'nilai' => isset($row['NILAI']) && is_numeric($row['NILAI']) ? (float) $row['NILAI'] : null,
            'pelaksana' => $pelaksana ?: null,
        ];
    }
}
