<?php

namespace App\Services\Sdg;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Skor SDGs per nagari dari endpoint tidak-resmi Kemendesa (sid.kemendesa.go.id).
 * Tanpa dokumentasi publik, tanpa API key, respons lambat (5-16 detik nyata) —
 * HANYA dipanggil dari job terjadwal (lihat SdgsRefreshKemendesa), bukan sinkron
 * per-request. Endpoint tak sopan soal error: kode tak valid balas HTTP 500
 * `{"message":"Server Error"}`, bukan 404 — jadi non-200 apa pun dianggap gagal.
 *
 * TANPA cache di level ini (beda dari BmkgWeatherService) — kesegaran data diatur
 * oleh jadwal command, bukan TTL cache.
 */
class SdgKemendesaService
{
    private const ENDPOINT = 'https://sid.kemendesa.go.id/sdgs/searching/score-sdgs';

    /**
     * @return array{average: float, goals: array<int, float>}|null
     */
    public function fetch(string $kodeBps): ?array
    {
        try {
            // Batas connect 30 detik: server Kemendesa memakan ~10,3 detik hanya
            // untuk menerima koneksi TCP. Batas yang lebih rapat memutusnya tepat
            // sebelum sambungan jadi, dan "cURL error 28" yang muncul mudah disalah-
            // artikan sebagai hosting yang memblokir koneksi keluar.
            $proxyUrl = env('KEMENDESA_PROXY_URL');
            
            if (!empty($proxyUrl)) {
                $endpoint = self::ENDPOINT . '?location_code=' . $kodeBps;
                $urlToFetch = rtrim($proxyUrl, '/');
                $query = ['url' => $endpoint];
            } else {
                $urlToFetch = self::ENDPOINT;
                $query = ['location_code' => $kodeBps];
            }

            $response = Http::connectTimeout(30)
                ->timeout(45)
                // Ulang HANYA saat gangguan koneksi (DNS/timeout) — bukan HTTP 500,
                // yang di endpoint ini juga berarti "kode BPS tak ditemukan" (respons
                // deterministik, mengulang tak akan mengubah hasil, cuma buang waktu
                // & membebani server mereka tanpa alasan).
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
                Log::info('Kemendesa score-sdgs: respons tak sukses (kode tak valid atau server error)', [
                    'kode_bps' => $kodeBps,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->json();

            if (! is_array($body) || ! isset($body['average'], $body['data']) || ! is_array($body['data'])) {
                Log::warning('Kemendesa score-sdgs: struktur respons tak sesuai dugaan', ['kode_bps' => $kodeBps]);

                return null;
            }

            $goals = [];

            foreach ($body['data'] as $entry) {
                if (! is_array($entry) || ! isset($entry['goals'], $entry['score'])) {
                    Log::warning('Kemendesa score-sdgs: entri "data" tak sesuai dugaan', ['kode_bps' => $kodeBps]);

                    return null;
                }

                $goals[(int) $entry['goals']] = (float) $entry['score'];
            }

            if (count($goals) !== 18) {
                Log::warning('Kemendesa score-sdgs: jumlah poin bukan 18', [
                    'kode_bps' => $kodeBps,
                    'jumlah' => count($goals),
                ]);

                return null;
            }

            return [
                'average' => (float) $body['average'],
                'goals' => $goals,
            ];
        } catch (\Throwable $e) {
            Log::warning('Kemendesa score-sdgs: gagal diambil', [
                'kode_bps' => $kodeBps,
                'pesan' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
