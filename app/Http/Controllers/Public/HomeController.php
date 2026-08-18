<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Nagari;
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

        $mitraNagari = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->with('media')
            ->orderBy('nama')
            ->take(8)
            ->get();

        return view('public.home', [
            'faqs' => $faqs,
            'metrics' => $this->overview->metrikEkosistem(),
            'mitraNagari' => $mitraNagari,
        ]);
    }

    /** Pilar 3 tingkat induk. Tanpa data: statusnya masih dalam perencanaan. */
    public function bapaneh(): View
    {
        return view('public.bapaneh');
    }

}
