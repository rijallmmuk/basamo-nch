<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Enums\ModuleStatus;
use App\Enums\UmkmProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Module;
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
            'desa' => Desa::where('status', ActiveStatus::Active)->count(),
            'produk' => UmkmProduct::where('status', UmkmProductStatus::Approved)->count(),
            'modul' => Module::where('status', ModuleStatus::Published)->count(),
        ]);

        // Produk unggulan untuk showcase beranda (4 terbaru yang disetujui).
        $produkUnggulan = UmkmProduct::query()
            ->where('status', UmkmProductStatus::Approved)
            ->whereHas('umkmProfile', fn ($q) => $q->where('status', ActiveStatus::Active))
            ->with(['umkmProfile.desa.jenisDesa', 'media'])
            ->latest('approved_at')
            ->take(4)
            ->get();

        return view('public.home', [
            'stats' => $stats,
            'produkUnggulan' => $produkUnggulan,
        ]);
    }
}
