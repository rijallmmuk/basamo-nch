<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Enums\UmkmProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Desa;
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
            ->where('status', UmkmProductStatus::Approved)
            ->whereHas('umkmProfile', fn ($q) => $q->where('status', ActiveStatus::Active))
            ->with(['umkmProfile.desa.jenisDesa', 'category', 'media'])
            ->when($request->filled('q'), fn ($q) => $this->applySearch($q, trim((string) $request->input('q'))))
            ->when($request->filled('desa'), fn ($q) => $q->whereHas('umkmProfile', fn ($p) => $p->where('desa_id', $request->integer('desa'))))
            ->when($request->filled('kategori'), fn ($q) => $q->where('umkm_category_id', $request->integer('kategori')))
            ->latest('approved_at')
            ->simplePaginate(12)
            ->withQueryString();

        return view('public.umkm.index', [
            'products' => $products,
            'desaList' => Cache::remember(
                'umkm.catalog.desa_list.v2',
                now()->addHour(),
                fn () => Desa::where('status', ActiveStatus::Active)->with('jenisDesa')->orderBy('nama')->get()
                    ->mapWithKeys(fn (Desa $desa) => [$desa->id => $desa->nama_lengkap]),
            ),
            'kategoriList' => Cache::remember(
                'umkm.catalog.kategori_list',
                now()->addHour(),
                fn () => UmkmCategory::orderBy('urutan')->pluck('nama', 'id'),
            ),
            'filters' => $request->only(['q', 'desa', 'kategori']),
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
            $product->status === UmkmProductStatus::Approved && $product->umkmProfile?->status === ActiveStatus::Active,
            404
        );

        // Hitung kunjungan tanpa membump updated_at (atomik), maksimum sekali per
        // pengunjung per 6 jam. Cache::add atomik → cegah inflasi refresh/bot &
        // write amplification di skala nasional.
        $seenKey = 'umkm.view.'.sha1($request->ip().'|'.$product->getKey());

        if (Cache::add($seenKey, true, now()->addHours(6))) {
            $product->newQuery()->whereKey($product->getKey())
                ->update(['jumlah_dilihat' => DB::raw('jumlah_dilihat + 1')]);
        }

        $product->load(['umkmProfile.desa.jenisDesa', 'category', 'media']);

        return view('public.umkm.show', ['product' => $product]);
    }
}
