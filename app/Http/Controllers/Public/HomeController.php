<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\UmkmProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Landing page publik (tanpa login). Ringkasan platform + pintu ke katalog UMKM
 * dan portal. Statistik di-cache (halaman ramai, data berubah lambat).
 */
class HomeController extends Controller
{
    public function index(): View
    {
        $stats = Cache::remember('public.home.stats', now()->addHour(), fn (): array => [
            'nagari' => Nagari::where('status', 'active')->count(),
            'produk' => UmkmProduct::where('status', 'approved')->count(),
            'modul' => Module::where('status', 'published')->count(),
        ]);

        return view('public.home', ['stats' => $stats]);
    }
}
