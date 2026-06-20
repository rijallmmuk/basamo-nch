<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            ->when($request->filled('q'), fn ($q) => $this->applySearch($q, trim((string) $request->input('q'))))
            ->when($request->filled('nagari'), fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('nagari_id', $request->integer('nagari'))))
            ->when($request->filled('kategori'), fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('umkm_category_id', $request->integer('kategori'))))
            ->latest('approved_at')
            ->simplePaginate(12)
            ->withQueryString();

        return view('public.umkm.index', [
            'products' => $products,
            'nagariList' => Cache::remember(
                'umkm.catalog.nagari_list',
                now()->addHour(),
                fn () => Nagari::where('status', 'active')->orderBy('nama')->pluck('nama', 'id'),
            ),
            'kategoriList' => Cache::remember(
                'umkm.catalog.kategori_list',
                now()->addHour(),
                fn () => UmkmCategory::orderBy('sort_order')->pluck('nama', 'id'),
            ),
            'filters' => $request->only(['q', 'nagari', 'kategori']),
        ]);
    }

    /**
     * Pencarian katalog. Di MySQL & term ≥3 huruf pakai index FULLTEXT (boolean +
     * wildcard awalan) agar sargable di skala nasional. Selain itu (term pendek —
     * FULLTEXT mengabaikan token di bawah panjang minimum — atau driver non-MySQL
     * seperti sqlite di test) jatuh ke LIKE.
     *
     * @param  Builder<UmkmProduct>  $query
     */
    private function applySearch(Builder $query, string $term): void
    {
        // Bersihkan operator boolean FULLTEXT dari input pengguna agar tak memicu
        // sintaks tak valid (+ - > < ( ) ~ * " @).
        $words = collect(preg_split('/\s+/', $term))
            ->map(fn (string $word) => preg_replace('/[+\-><()~*"@]/', '', $word))
            ->filter();

        if ($query->getConnection()->getDriverName() !== 'mysql' || mb_strlen($term) < 3 || $words->isEmpty()) {
            $query->where('nama_produk', 'like', '%'.$term.'%');

            return;
        }

        $boolean = $words->map(fn (string $word) => '+'.$word.'*')->implode(' ');

        $query->whereFullText(['nama_produk', 'deskripsi'], $boolean, ['mode' => 'boolean']);
    }

    public function show(Request $request, UmkmProduct $product): View
    {
        abort_unless(
            $product->status === 'approved' && $product->umkmProfile?->status === 'active',
            404
        );

        // Hitung kunjungan tanpa membump updated_at (atomik), maksimum sekali per
        // pengunjung per 6 jam. Cache::add atomik → cegah inflasi refresh/bot &
        // write amplification di skala nasional.
        $seenKey = 'umkm.view.'.sha1($request->ip().'|'.$product->getKey());

        if (Cache::add($seenKey, true, now()->addHours(6))) {
            $product->newQuery()->whereKey($product->getKey())
                ->update(['view_count' => DB::raw('view_count + 1')]);
        }

        $product->load(['umkmProfile.nagari', 'umkmProfile.category', 'media']);

        return view('public.umkm.show', ['product' => $product]);
    }
}
