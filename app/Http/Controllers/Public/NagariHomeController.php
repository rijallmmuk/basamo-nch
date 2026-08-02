<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Services\Ews\EwsPanelService;
use App\Services\PublicOverviewService;
use App\Services\PublicTerasDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Wajah publik per nagari di subdomain {slug}.PUBLIC_BASE_DOMAIN: beranda (sambutan,
 * Teras data, donat SDGs, dan UMKM unggulan) serta halaman capaian SDGs Desa.
 */
class NagariHomeController extends Controller
{
    public function __construct(
        private readonly PublicOverviewService $overview,
        private readonly EwsPanelService $ews,
        private readonly PublicTerasDataService $terasData,
    ) {}

    public function index(Nagari $nagari): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);
        $nagari->loadMissing('media');

        // Cuplikan pelatihan dan UMKM DIHAPUS dari beranda. Keduanya mengulang isi
        // halaman pilarnya sendiri dengan versi yang lebih miskin, dan menyeret dua
        // query berat ke halaman yang paling sering dibuka. Beranda kini menyerahkan
        // pengunjung ke pilarnya, bukan mencoba menjadi semuanya sekaligus.
        $overview = $this->overview->overview($nagari);

        return view('public.nagari.home', [
            'nagari' => $nagari,
            'overview' => $overview,
            'fotoSampul' => $nagari->sampulUrls(),
            'ews' => $this->ews->untukNagari($nagari),
        ]);
    }

    /** Halaman publik Teras Nagari: Detail Analitik SDGs, IDM, Cuaca, Demografi SID. */
    public function teras(Nagari $nagari, Request $request): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);
        return view('public.nagari.teras', $this->terasData->untukNagari($nagari, $request));
    }

    /** Halaman publik Medan Nan Bapaneh: Budaya & Inovasi (Coming Soon). */
    public function bapaneh(Nagari $nagari): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        return view('public.nagari.bapaneh', [
            'nagari' => $nagari,
        ]);
    }
}
