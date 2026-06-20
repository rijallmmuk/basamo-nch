<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Katalog UMKM publik (tanpa login). Hanya produk approved dari usaha aktif.
 */
class UmkmCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $products = UmkmProduct::query()
            ->where('status', 'approved')
            ->whereHas('umkmProfile', fn ($q) => $q->where('status', 'active'))
            ->with(['umkmProfile.nagari', 'umkmProfile.category', 'media'])
            ->when($request->filled('q'), fn ($q) => $q->where('nama_produk', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('nagari'), fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('nagari_id', $request->integer('nagari'))))
            ->when($request->filled('kategori'), fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('umkm_category_id', $request->integer('kategori'))))
            ->latest('approved_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.umkm.index', [
            'products' => $products,
            'nagariList' => Nagari::where('status', 'active')->orderBy('nama')->pluck('nama', 'id'),
            'kategoriList' => UmkmCategory::orderBy('sort_order')->pluck('nama', 'id'),
            'filters' => $request->only(['q', 'nagari', 'kategori']),
        ]);
    }

    public function show(UmkmProduct $product): View
    {
        abort_unless(
            $product->status === 'approved' && $product->umkmProfile->status === 'active',
            404
        );

        // Hitung kunjungan tanpa membump updated_at (atomik).
        $product->newQuery()->whereKey($product->getKey())
            ->update(['view_count' => DB::raw('view_count + 1')]);

        $product->load(['umkmProfile.nagari', 'umkmProfile.category', 'media']);

        return view('public.umkm.show', ['product' => $product]);
    }
}
