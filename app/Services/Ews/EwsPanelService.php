<?php

namespace App\Services\Ews;

use App\Enums\StatusSungai;
use App\Models\EwsDevice;
use App\Models\EwsReading;
use App\Models\Nagari;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Bahan tampilan pemantauan EWS untuk halaman publik dan panel.
 *
 * Halaman dirender dari pembacaan TERAKHIR yang sudah tersimpan, bukan dengan
 * menunggu Blynk. Satu panggilan Blynk memakan sekitar 2,5 detik, dan halaman
 * kebencanaan justru harus terbuka cepat. Penyegaran nilai dilakukan terpisah
 * lewat endpoint JSON yang membaca cache.
 */
class EwsPanelService
{
    /**
     * Pembacaan dianggap BASI setelah ini (menit). Penjadwal merekam tiap 5 menit,
     * jadi 20 menit berarti empat siklus terlewat: sudah pantas dikatakan kepada
     * pembaca, bukan disembunyikan.
     */
    public const BATAS_BASI_MENIT = 20;

    /** Rentang bawaan grafik tren (jam). */
    public const RENTANG_TREN_JAM = 24;

    /**
     * @return array{
     *     device: EwsDevice, pembacaan: ?EwsReading, status: StatusSungai,
     *     terhubung: bool, basi: bool, tren: array{labels: list<string>,
     *     tinggi_air: list<?float>, curah_hujan: list<?float>}
     * }|null
     */
    public function untukNagari(Nagari $nagari, int $rentangJam = self::RENTANG_TREN_JAM): ?array
    {
        $device = EwsDevice::query()
            ->siapPakai()
            ->where('nagari_id', $nagari->getKey())
            ->with('nagari')
            ->first();

        return $device ? $this->untukPerangkat($device, $rentangJam) : null;
    }

    /**
     * @return array{
     *     device: EwsDevice, pembacaan: ?EwsReading, status: StatusSungai,
     *     terhubung: bool, basi: bool, tren: array{labels: list<string>,
     *     tinggi_air: list<?float>, curah_hujan: list<?float>}
     * }
     */
    public function untukPerangkat(EwsDevice $device, int $rentangJam = self::RENTANG_TREN_JAM): array
    {
        $pembacaan = $device->relationLoaded('pembacaanTerakhir')
            ? $device->pembacaanTerakhir
            : $device->readings()->latest('direkam_pada')->first();

        return [
            'device' => $device,
            'pembacaan' => $pembacaan,
            'status' => $pembacaan?->status() ?? StatusSungai::TidakDiketahui,
            'terhubung' => (bool) $pembacaan?->terhubung,
            'basi' => $this->basi($pembacaan),
            'tren' => $this->tren($device, $rentangJam),
        ];
    }

    /**
     * Pembacaan terkini untuk penyegaran halaman (endpoint JSON). Membaca cache
     * hasil penjadwal; hanya menembak Blynk bila cache sudah kedaluwarsa.
     *
     * @return array<string, mixed>
     */
    public function terkiniUntukTampilan(EwsDevice $device, EwsBlynkService $blynk): array
    {
        $segar = $blynk->terkini($device);

        if ($segar !== null) {
            $status = StatusSungai::dariTeks($segar['status_sungai']);
            $ph = $segar['ph_air'];

            return [
                'tinggi_air' => $segar['tinggi_air'],
                'curah_hujan' => $segar['curah_hujan'],
                'ph_air' => $ph,
                'ph_mencurigakan' => $ph !== null && ($ph < EwsReading::PH_MIN || $ph > EwsReading::PH_MAKS),
                'getaran' => $segar['getaran'],
                'status' => $status->value,
                'status_label' => $status->getLabel(),
                'status_kelas' => $status->kelasWarna(),
                'terhubung' => $segar['terhubung'],
                'diambil_pada' => $segar['diambil_pada']->toIso8601String(),
                'diambil_pada_manusia' => $segar['diambil_pada']->locale('id')->diffForHumans(),
                'basi' => false,
            ];
        }

        // Blynk tak terjangkau: JANGAN diam-diam menampilkan angka lama seolah baru.
        // Kembalikan pembacaan tersimpan berikut penandanya, biar halaman jujur.
        $pembacaan = $device->readings()->latest('direkam_pada')->first();
        $status = $pembacaan?->status() ?? StatusSungai::TidakDiketahui;

        return [
            'tinggi_air' => $pembacaan?->tinggi_air,
            'curah_hujan' => $pembacaan?->curah_hujan,
            'ph_air' => $pembacaan?->ph_air,
            'ph_mencurigakan' => (bool) $pembacaan?->phMencurigakan(),
            'getaran' => $pembacaan?->getaran,
            'status' => $status->value,
            'status_label' => $status->getLabel(),
            'status_kelas' => $status->kelasWarna(),
            'terhubung' => (bool) $pembacaan?->terhubung,
            'diambil_pada' => $pembacaan?->direkam_pada?->toIso8601String(),
            'diambil_pada_manusia' => $pembacaan?->direkam_pada?->locale('id')->diffForHumans(),
            'basi' => true,
        ];
    }

    private function basi(?EwsReading $pembacaan): bool
    {
        if (! $pembacaan?->direkam_pada) {
            return true;
        }

        return $pembacaan->direkam_pada->lt(now()->subMinutes(self::BATAS_BASI_MENIT));
    }

    /**
     * Deret tren tinggi air dan curah hujan.
     *
     * Titik yang kosong dibiarkan null, TIDAK diganti nol. Pada tinggi air, nol
     * berarti sungai kering dan di grafik akan terbaca sebagai penurunan drastis
     * yang tidak pernah terjadi.
     *
     * @return array{labels: list<string>, tinggi_air: list<?float>, curah_hujan: list<?float>}
     */
    private function tren(EwsDevice $device, int $rentangJam): array
    {
        /** @var Collection<int, EwsReading> $baris */
        $baris = $device->readings()
            ->sejak(CarbonImmutable::now()->subHours(max(1, $rentangJam)))
            ->orderBy('direkam_pada')
            ->get(['tinggi_air', 'curah_hujan', 'direkam_pada']);

        return [
            'labels' => $baris->map(fn (EwsReading $r): string => $r->direkam_pada->format('H:i'))->values()->all(),
            'tinggi_air' => $baris->map(fn (EwsReading $r): ?float => $r->tinggi_air)->values()->all(),
            'curah_hujan' => $baris->map(fn (EwsReading $r): ?float => $r->curah_hujan)->values()->all(),
        ];
    }
}
