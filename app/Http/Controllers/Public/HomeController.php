<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Enums\StatusSungai;
use App\Http\Controllers\Controller;
use App\Models\EwsDevice;
use App\Models\EwsReading;
use App\Models\Faq;
use App\Models\Nagari;
use App\Services\Ews\EwsPanelService;
use App\Services\PublicOverviewService;
use Illuminate\Contracts\View\View;

/**
 * Beranda situs induk: dasbor platform lintas nagari.
 *
 * Seluruh angka dibaca dari basis data. Halaman ini dilayankan ke publik, jadi
 * TIDAK BOLEH ada angka simulasi atau nilai contoh yang di-hardcode.
 *
 * Susunannya mengikuti dasbor superadmin, tanpa satu pun data pribadi.
 */
class HomeController extends Controller
{
    public function __construct(private readonly PublicOverviewService $overview) {}

    public function index(): View
    {
        // Tanpa cache FAQ: harus sinkron seketika begitu superadmin menyimpan.
        $faqs = Faq::query()->where('aktif', true)->orderBy('urutan')->get();

        $overview = $this->overview->overview(lengkap: false);

        $mitraNagari = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->with('media')
            ->orderBy('nama')
            ->take(8)
            ->get();

        return view('public.home', [
            'faqs' => $faqs,
            'overview' => $overview,
            'mitraNagari' => $mitraNagari,
            'ewsTitik' => $this->ringkasanEws(),
        ]);
    }

    /** Pilar 3 tingkat induk. Tanpa data: statusnya masih dalam perencanaan. */
    public function bapaneh(): View
    {
        return view('public.bapaneh');
    }

    /**
     * Ringkasan titik pantau EWS lintas nagari untuk pengantar pemantauan di Teras.
     *
     * Dibaca dari pembacaan TERSIMPAN, tidak menembak Blynk: beranda induk harus
     * terbuka cepat, dan satu panggilan Blynk memakan sekitar 2,5 detik. Deret tren
     * pun tidak diambil di sini, cukup nilai terakhirnya; grafiknya ada di halaman
     * IoT dan Teras Nagari.
     *
     * @return list<array{device: EwsDevice, pembacaan: ?EwsReading, status: StatusSungai, terhubung: bool, tepercaya: bool}>
     */
    private function ringkasanEws(): array
    {
        $batasBasi = now()->subMinutes(EwsPanelService::BATAS_BASI_MENIT);

        return EwsDevice::query()
            ->siapPakai()
            ->with(['nagari', 'pembacaanTerakhir'])
            ->get()
            ->map(function (EwsDevice $device) use ($batasBasi): array {
                $pembacaan = $device->pembacaanTerakhir;

                return [
                    'device' => $device,
                    'pembacaan' => $pembacaan,
                    'status' => $pembacaan?->status() ?? StatusSungai::TidakDiketahui,
                    'terhubung' => (bool) $pembacaan?->terhubung,
                    // Status hanya boleh tampil meyakinkan bila alatnya benar-benar
                    // terhubung DAN pembacaannya masih baru. "Aman" berwarna hijau
                    // dari alat yang mati tiga jam lalu adalah kalimat yang salah,
                    // dan pada halaman kebencanaan salahnya berbahaya.
                    'tepercaya' => $pembacaan !== null
                        && $pembacaan->terhubung
                        && $pembacaan->direkam_pada->gte($batasBasi),
                ];
            })
            ->all();
    }
}
