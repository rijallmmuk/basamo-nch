<?php

namespace App\Services;

use App\Models\Nagari;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Prakiraan cuaca publik BMKG (data.bmkg.go.id) per kode wilayah adm4 — kode
 * yang sama persis dengan `nagari.wilayah_kode` (Kepmendagri), jadi tak perlu
 * kolom terpisah. BMKG memperbarui data 2x/hari & menyediakan prakiraan per
 * jam untuk 3 hari ke depan (bukan 10 — sudah dicek langsung ke API).
 *
 * Ketahanan produksi:
 * - Gagal ambil TIDAK BOLEH merusak halaman publik — selalu null, jangan throw.
 * - connectTimeout pendek + 1 retry singkat untuk gangguan sesaat, TANPA retry
 *   berlebihan (tiap request publik menunggu hasil ini secara sinkron).
 * - Kegagalan ikut di-cache (durasi pendek) — bukan cuma sukses — supaya saat
 *   BMKG benar-benar down, TIDAK setiap request publik ikut menunggu timeout;
 *   ini juga menghindari kuirk Cache::remember() yang tak pernah men-cache null.
 * - SINGLE-FLIGHT lock saat cache kosong: hanya satu request menembak BMKG, sisanya
 *   membaca hasil cache-nya — cegah stampede yang bisa menjebol 60/menit saat trafik
 *   publik memuncak tepat ketika cache kedaluwarsa.
 * - Rate limit BMKG 60/menit per IP: cache 3 jam × belasan nagari + single-flight
 *   membuat panggilan jauh di bawah batas walau di publik.
 *
 * Wajib tampilkan atribusi "BMKG" di sisi tampilan (syarat resmi BMKG).
 */
class BmkgWeatherService
{
    private const ENDPOINT = 'https://api.bmkg.go.id/publik/prakiraan-cuaca';

    private const CACHE_SUKSES = 180; // menit (3 jam) — selaras jadwal update BMKG 2x/hari.

    private const CACHE_GAGAL = 10; // menit — batasi dampak saat BMKG down, cepat pulih begitu API normal lagi.

    /** Penanda kegagalan di cache — beda dari null "belum pernah dicoba" & dari hasil array sukses. */
    private const GAGAL = '__bmkg_gagal__';

    /** Terjemahan arah mata angin BMKG (kode kompas) ke Bahasa Indonesia. */
    private const ARAH_ANGIN = [
        'N' => 'Utara', 'NNE' => 'Utara-Timur Laut', 'NE' => 'Timur Laut', 'ENE' => 'Timur-Timur Laut',
        'E' => 'Timur', 'ESE' => 'Timur-Tenggara', 'SE' => 'Tenggara', 'SSE' => 'Selatan-Tenggara',
        'S' => 'Selatan', 'SSW' => 'Selatan-Barat Daya', 'SW' => 'Barat Daya', 'WSW' => 'Barat-Barat Daya',
        'W' => 'Barat', 'WNW' => 'Barat-Barat Laut', 'NW' => 'Barat Laut', 'NNW' => 'Utara-Barat Laut',
    ];

    /**
     * Prakiraan lengkap per jam, dikelompokkan per hari.
     *
     * @return array{lokasi: ?string, saat_ini: ?array, hari: array<int, array{tanggal: string, slots: array}>}|null
     */
    public function prakiraan(Nagari $nagari): ?array
    {
        if (! $nagari->wilayah_kode) {
            return null;
        }

        $key = 'public.nagari.cuaca.v4.'.$nagari->wilayah_kode;
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached === self::GAGAL ? null : $cached;
        }

        return $this->ambilTerkunci($key, $nagari->wilayah_kode);
    }

    /**
     * Isi cache dengan penguncian SINGLE-FLIGHT: saat cache kosong, HANYA satu request
     * yang menembak BMKG; request lain menunggu sebentar lalu membaca hasil cache-nya.
     * Mencegah stampede yang bisa menjebol batas 60/menit BMKG (semua panggilan berasal
     * dari satu IP server) ketika trafik publik memuncak tepat saat cache kedaluwarsa.
     */
    private function ambilTerkunci(string $key, string $adm4): ?array
    {
        $lock = Cache::lock($key.':lock', 15);

        try {
            // Tunggu maks 7 detik pemegang lock (yang sedang menembak BMKG) selesai.
            $lock->block(7);
        } catch (LockTimeoutException) {
            // Tak dapat lock: request lain masih mengambil → JANGAN ikut menembak BMKG.
            // Pakai cache bila sudah terisi si pemegang, selain itu null (halaman tetap hidup).
            $cached = Cache::get($key);

            return ($cached === null || $cached === self::GAGAL) ? null : $cached;
        }

        try {
            // Pemegang lock sebelumnya mungkin sudah mengisi cache — jangan menembak dobel.
            $cached = Cache::get($key);

            if ($cached !== null) {
                return $cached === self::GAGAL ? null : $cached;
            }

            $hasil = $this->ambil($adm4);

            Cache::put(
                $key,
                $hasil ?? self::GAGAL,
                now()->addMinutes($hasil !== null ? self::CACHE_SUKSES : self::CACHE_GAGAL),
            );

            return $hasil;
        } finally {
            $lock->release();
        }
    }

    private function ambil(string $adm4): ?array
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(6)
                // retry() Laravel menghitung TOTAL percobaan (bukan tambahan) — 2 di sini
                // berarti 1 percobaan awal + 1 kali ulang saat gangguan koneksi sesaat.
                ->retry(2, 300)
                ->get(self::ENDPOINT, ['adm4' => $adm4]);

            if (! $response->successful()) {
                Log::warning('BMKG prakiraan-cuaca: respons tak sukses', [
                    'adm4' => $adm4,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->json();

            if (! is_array($body)) {
                Log::warning('BMKG prakiraan-cuaca: respons bukan JSON valid', ['adm4' => $adm4]);

                return null;
            }

            $hariMentah = data_get($body, 'data.0.cuaca');

            if (! is_array($hariMentah) || $hariMentah === []) {
                Log::warning('BMKG prakiraan-cuaca: struktur "data.0.cuaca" tak sesuai dugaan', ['adm4' => $adm4]);

                return null;
            }

            $hari = collect($hariMentah)
                ->filter(fn ($slots): bool => is_array($slots) && $slots !== [] && isset($slots[0]['local_datetime']))
                ->map(fn (array $slots): array => [
                    'tanggal' => Carbon::parse($slots[0]['local_datetime'])->translatedFormat('d M Y'),
                    'slots' => collect($slots)->map(fn (array $s): array => $this->parseSlot($s))->all(),
                ])
                ->values()
                ->all();

            if ($hari === []) {
                Log::warning('BMKG prakiraan-cuaca: tak ada slot valid setelah diproses', ['adm4' => $adm4]);

                return null;
            }

            return [
                // 'desa' = nama field ASLI dari respons BMKG (istilah mereka, bukan milik
                // kita) — JANGAN pernah ganti ke 'nagari' walau sapuan rename lain terjadi.
                'lokasi' => data_get($body, 'lokasi.desa'),
                'saat_ini' => $hari[0]['slots'][0] ?? null,
                'hari' => $hari,
            ];
        } catch (\Throwable $e) {
            Log::warning('BMKG prakiraan-cuaca: gagal diambil', [
                'adm4' => $adm4,
                'pesan' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array<string, mixed> */
    private function parseSlot(array $slot): array
    {
        return [
            'jam' => isset($slot['local_datetime']) ? Carbon::parse($slot['local_datetime'])->format('H.i') : null,
            'suhu' => $slot['t'] ?? null,
            'kelembapan' => $slot['hu'] ?? null,
            'kondisi' => $slot['weather_desc'] ?? null,
            'ikon' => $slot['image'] ?? null,
            'waktu_lokal' => $slot['local_datetime'] ?? null,
            'kecepatan_angin' => $slot['ws'] ?? null,
            'arah_angin_dari' => self::ARAH_ANGIN[$slot['wd'] ?? ''] ?? ($slot['wd'] ?? null),
            // Arah panah = tujuan angin (kebalikan arah asal) — dihitung dari derajat sumber BMKG.
            'arah_derajat_tujuan' => isset($slot['wd_deg']) ? ($slot['wd_deg'] + 180) % 360 : null,
            'jarak_pandang' => $slot['vs_text'] ?? null,
        ];
    }
}
