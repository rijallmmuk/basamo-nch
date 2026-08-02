<?php

namespace App\Services\Ews;

use App\Enums\StatusSungai;
use App\Models\EwsDevice;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pembacaan sensor EWS banjir bandang dari Blynk Cloud.
 *
 * TOKEN TIDAK PERNAH SAMPAI KE PERAMBAN. Contoh dashboard yang jadi acuan menaruh
 * token di JavaScript, sehingga siapa pun yang membuka halaman bisa membacanya di
 * view-source. Token Blynk bukan kredensial baca-saja: endpoint `update` memakai
 * token yang sama, jadi orang asing dapat menulis nilai palsu ke alat peringatan
 * dini banjir. Di sini server yang menembak Blynk, halaman membaca hasilnya dari
 * server kita sendiri.
 *
 * SATU PERMINTAAN PER LOKASI, bukan lima. Blynk menerima banyak pin sekaligus
 * (`&v0&v1&v2&v5&v6`) dan menjawab JSON. Acuan lama menembak 5 kali tiap 3 detik
 * PER PENGUNJUNG; beberapa pembuka halaman sekaligus sudah cukup menembus batas
 * laju Blynk dan mematikan pemantauan justru saat paling dibutuhkan.
 *
 * Ketahanan mengikuti pola BmkgWeatherService yang sudah terbukti di proyek ini:
 * - Gagal ambil TIDAK BOLEH merusak halaman publik: selalu null, jangan throw.
 * - Kegagalan ikut di-cache (durasi pendek) supaya saat Blynk down tidak setiap
 *   pengunjung menunggu timeout.
 * - Single-flight lock: saat cache kosong hanya satu request yang menembak Blynk.
 */
class EwsBlynkService
{
    private const ENDPOINT_GET = 'https://sgp1.blynk.cloud/external/api/get';

    private const ENDPOINT_STATUS = 'https://sgp1.blynk.cloud/external/api/isHardwareConnected';

    /**
     * Pin virtual perangkat → nama kolom kita.
     *
     * @var array<string, string>
     */
    public const PIN = [
        'v0' => 'tinggi_air',
        'v1' => 'curah_hujan',
        'v2' => 'ph_air',
        'v5' => 'getaran',
        'v6' => 'status_sungai',
    ];

    /**
     * Detik. Pendek karena ini peringatan dini: data basi setengah menit masih
     * berguna, data basi setengah jam tidak. Tetap jauh di bawah batas laju Blynk
     * karena seluruh pengunjung berbagi satu hasil.
     */
    private const CACHE_SUKSES = 30;

    /** Detik. Batasi dampak saat Blynk down, tetap cepat pulih begitu normal. */
    private const CACHE_GAGAL = 60;

    private const GAGAL = '__ews_gagal__';

    /**
     * Pembacaan terkini satu perangkat, dari cache bila masih hangat.
     *
     * @return array{
     *     tinggi_air: ?float, curah_hujan: ?float, ph_air: ?float, getaran: ?float,
     *     status_sungai: ?string, terhubung: bool, diambil_pada: CarbonImmutable
     * }|null
     */
    public function terkini(EwsDevice $device): ?array
    {
        $key = $this->cacheKey($device);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached === self::GAGAL ? null : $this->hidupkanWaktu($cached);
        }

        return $this->ambilTerkunci($key, $device);
    }

    /** Paksa ambil dari Blynk tanpa membaca cache (dipakai penjadwal perekam riwayat). */
    public function ambilSegar(EwsDevice $device): ?array
    {
        $hasil = $this->ambil($device);

        Cache::put(
            $this->cacheKey($device),
            $hasil !== null ? $this->bekukanWaktu($hasil) : self::GAGAL,
            now()->addSeconds($hasil !== null ? self::CACHE_SUKSES : self::CACHE_GAGAL),
        );

        return $hasil;
    }

    public function lupakanCache(EwsDevice $device): void
    {
        Cache::forget($this->cacheKey($device));
    }

    private function cacheKey(EwsDevice $device): string
    {
        return 'ews.terkini.v1.'.$device->getKey();
    }

    /**
     * Single-flight: saat cache kosong hanya satu request menembak Blynk, sisanya
     * menunggu sebentar lalu membaca hasilnya.
     */
    private function ambilTerkunci(string $key, EwsDevice $device): ?array
    {
        $lock = Cache::lock($key.':lock', 15);

        try {
            $lock->block(5);
        } catch (LockTimeoutException) {
            $cached = Cache::get($key);

            return ($cached === null || $cached === self::GAGAL) ? null : $this->hidupkanWaktu($cached);
        }

        try {
            $cached = Cache::get($key);

            if ($cached !== null) {
                return $cached === self::GAGAL ? null : $this->hidupkanWaktu($cached);
            }

            $hasil = $this->ambil($device);

            Cache::put(
                $key,
                $hasil !== null ? $this->bekukanWaktu($hasil) : self::GAGAL,
                now()->addSeconds($hasil !== null ? self::CACHE_SUKSES : self::CACHE_GAGAL),
            );

            return $hasil;
        } finally {
            $lock->release();
        }
    }

    private function ambil(EwsDevice $device): ?array
    {
        $token = (string) $device->blynk_token;

        if (trim($token) === '') {
            return null;
        }

        try {
            // Pin dirangkai manual: Blynk mengharapkan parameter TANPA nilai
            // (`&v0&v1`), bentuk yang tidak bisa dinyatakan lewat array query
            // biasa karena akan jadi `v0=&v1=`.
            $url = self::ENDPOINT_GET.'?token='.urlencode($token).'&'.implode('&', array_keys(self::PIN));

            $response = Http::connectTimeout(3)
                ->timeout(6)
                // retry() Laravel menghitung TOTAL percobaan: 2 = 1 awal + 1 ulang.
                ->retry(2, 300)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('EWS Blynk: respons tak sukses', [
                    'device' => $device->getKey(),
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->json();

            if (! is_array($body)) {
                Log::warning('EWS Blynk: respons bukan JSON', ['device' => $device->getKey()]);

                return null;
            }

            return [
                ...$this->petakanPin($body),
                'terhubung' => $this->terhubung($token),
                'diambil_pada' => CarbonImmutable::now(),
            ];
        } catch (\Throwable $e) {
            Log::warning('EWS Blynk: gagal diambil', [
                'device' => $device->getKey(),
                'pesan' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, float|string|null>
     */
    private function petakanPin(array $body): array
    {
        $hasil = [];

        foreach (self::PIN as $pin => $kolom) {
            $nilai = $body[$pin] ?? null;

            if ($kolom === 'status_sungai') {
                // Disimpan mentah; penerjemahannya di StatusSungai::dariTeks() supaya
                // teks asli tetap dapat ditelusuri saat sketch perangkat berubah.
                $hasil[$kolom] = is_scalar($nilai) && trim((string) $nilai) !== ''
                    ? mb_substr(trim((string) $nilai), 0, 30)
                    : null;

                continue;
            }

            $hasil[$kolom] = is_numeric($nilai) ? (float) $nilai : null;
        }

        return $hasil;
    }

    /**
     * Alat sedang tersambung? Nilai yang dikembalikan Blynk untuk alat yang mati
     * adalah pembacaan TERAKHIR yang ia ingat, tanpa penanda apa pun. Tanpa
     * pemeriksaan ini, angka berumur berminggu-minggu akan tampil seolah kondisi
     * saat ini. Kegagalan pemeriksaan dianggap "tidak terhubung", bukan sebaliknya.
     */
    private function terhubung(string $token): bool
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(5)
                ->get(self::ENDPOINT_STATUS, ['token' => $token]);

            return $response->successful()
                && filter_var(trim($response->body()), FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Status sungai terkini yang sudah dinormalkan. */
    public function status(?array $pembacaan): StatusSungai
    {
        return StatusSungai::dariTeks($pembacaan['status_sungai'] ?? null);
    }

    /**
     * Cache driver apa pun harus dapat menyimpan hasil ini, jadi waktu disimpan
     * sebagai string ISO, bukan objek Carbon.
     */
    private function bekukanWaktu(array $hasil): array
    {
        return [...$hasil, 'diambil_pada' => $hasil['diambil_pada']->toIso8601String()];
    }

    private function hidupkanWaktu(array $hasil): array
    {
        return [...$hasil, 'diambil_pada' => CarbonImmutable::parse($hasil['diambil_pada'])];
    }
}
