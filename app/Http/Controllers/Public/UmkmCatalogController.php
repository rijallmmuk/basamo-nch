<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\UmkmView;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Wajah publik UMKM per nagari (subdomain {slug}.domain): entri utama = DIREKTORI
 * usaha (bukan produk), tiap usaha punya halaman ETALASE sendiri berisi profil +
 * produknya, lalu detail produk. Tujuannya menjadikan tiap profil UMKM lapau usaha
 * pemiliknya. Yang tampil hanya lapak aktif dari nagari aktif; seluruh produk
 * lapak itu ikut tampil (produk tidak punya status terbit).
 */
class UmkmCatalogController extends Controller
{
    /** Direktori UMKM global lintas nagari aktif, dengan filter nagari. */
    public function globalDirectory(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $selectedNagariId = $request->integer('nagari') ?: null;
        $nagariOptions = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->orderBy('nama')
            ->get(['id', 'nama', 'slug', 'kabupaten']);

        if ($selectedNagariId && ! $nagariOptions->contains('id', $selectedNagariId)) {
            $selectedNagariId = null;
        }

        $usaha = UmkmProfile::query()
            ->where('status', ActiveStatus::Active)
            ->whereHas('nagari', fn ($query) => $query
                ->where('status', ActiveStatus::Active)
                ->when($selectedNagariId, fn ($nagaris) => $nagaris
                    ->whereKey($selectedNagariId)))
            ->with(['media', 'nagari:id,nama,slug,kabupaten'])
            ->withCount('products as produk_count')
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($matches) => $matches
                    ->where('nama_usaha', 'like', '%'.$search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$search.'%')
                    ->orWhereHas('products', fn ($products) => $products
                        ->where('nama_produk', 'like', '%'.$search.'%')
                        ->orWhere('deskripsi', 'like', '%'.$search.'%')
                        ->orWhereHas('category', fn ($categories) => $categories
                            ->where('nama', 'like', '%'.$search.'%')))))
            ->orderByDesc('produk_count')
            ->orderBy('nama_usaha')
            ->paginate(24)
            ->withQueryString();

        return view('public.umkm.directory', [
            'nagari' => null,
            'usaha' => $usaha,
            'nagariOptions' => $nagariOptions,
            'filters' => [
                'q' => $search,
                'nagari' => $selectedNagariId ? (string) $selectedNagariId : '',
            ],
        ]);
    }

    /** Direktori UMKM satu nagari: daftar usaha aktif + jumlah produknya. */
    public function directory(Request $request, Nagari $nagari): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);
        $search = trim((string) $request->input('q', ''));

        $usaha = UmkmProfile::query()
            ->where('nagari_id', $nagari->id)
            ->where('status', ActiveStatus::Active)
            ->with('media')
            ->withCount('products as produk_count')
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($matches) => $matches
                    ->where('nama_usaha', 'like', '%'.$search.'%')
                    ->orWhere('deskripsi', 'like', '%'.$search.'%')
                    ->orWhereHas('products', fn ($products) => $products
                        ->where('nama_produk', 'like', '%'.$search.'%')
                        ->orWhere('deskripsi', 'like', '%'.$search.'%')
                        ->orWhereHas('category', fn ($categories) => $categories
                            ->where('nama', 'like', '%'.$search.'%')))))
            ->orderByDesc('produk_count')
            ->orderBy('nama_usaha')
            ->paginate(24)
            ->withQueryString();

        return view('public.umkm.directory', [
            'nagari' => $nagari,
            'usaha' => $usaha,
            'nagariOptions' => collect(),
            'filters' => [
                'q' => $search,
                'nagari' => '',
            ],
        ]);
    }

    /** Etalase UMKM lintas nagari yang tetap berada pada domain utama. */
    public function globalEtalase(Request $request, UmkmProfile $umkmProfile): View
    {
        $profile = $umkmProfile->loadMissing('nagari');
        $nagari = $profile->nagari;

        abort_unless(
            $nagari?->status === ActiveStatus::Active && $profile->status === ActiveStatus::Active,
            404
        );

        $seenKey = 'umkm.profile.view.'.sha1($request->ip().'|'.$profile->getKey());
        if (Cache::add($seenKey, true, now()->addHours(6))) {
            $profile->newQuery()->whereKey($profile->getKey())
                ->update(['jumlah_dilihat' => DB::raw('jumlah_dilihat + 1')]);
            UmkmView::catat($profile);
        }

        return view('public.umkm.etalase', [
            'nagari' => $nagari,
            'profile' => $profile,
            ...$this->etalaseProducts($request, $profile),
            'global' => true,
        ]);
    }

    /**
     * Etalase satu usaha: profil lengkap (logo, sampul, tautan, QR) + produknya.
     * $umkmProfile ter-scope otomatis ke nagari via binding (Nagari::umkmProfiles()).
     */
    public function etalase(Request $request, Nagari $nagari, UmkmProfile $umkmProfile): View
    {
        $profile = $umkmProfile;

        abort_unless(
            $nagari->status === ActiveStatus::Active
                && $profile->nagari_id === $nagari->getKey()
                && $profile->status === ActiveStatus::Active,
            404
        );

        $seenKey = 'umkm.profile.view.'.sha1($request->ip().'|'.$profile->getKey());

        if (Cache::add($seenKey, true, now()->addHours(6))) {
            $profile->newQuery()->whereKey($profile->getKey())
                ->update(['jumlah_dilihat' => DB::raw('jumlah_dilihat + 1')]);

            // Rekap harian ditulis dari gerbang deduplikasi yang SAMA, supaya
            // penjumlahan hariannya tidak pernah menyimpang dari angka total.
            UmkmView::catat($profile);
        }

        return view('public.umkm.etalase', [
            'nagari' => $nagari,
            'profile' => $profile,
            ...$this->etalaseProducts($request, $profile),
        ]);
    }

    /** Detail produk di subdomain nagari — produk WAJIB milik nagari & usaha aktif. */
    public function show(Request $request, Nagari $nagari, UmkmProduct $product): View
    {
        abort_unless(
            $nagari->status === ActiveStatus::Active
                && $product->umkmProfile?->nagari_id === $nagari->getKey()
                && $product->umkmProfile->status === ActiveStatus::Active,
            404
        );

        // Hitung kunjungan tanpa membump updated_at (atomik), maksimum sekali per
        // pengunjung per 6 jam. Cache::add atomik → cegah inflasi refresh/bot &
        // write amplification di skala nasional.
        $seenKey = 'umkm.view.'.sha1($request->ip().'|'.$product->getKey());

        if (Cache::add($seenKey, true, now()->addHours(6))) {
            $product->newQuery()->whereKey($product->getKey())
                ->update(['jumlah_dilihat' => DB::raw('jumlah_dilihat + 1')]);

            UmkmView::catat($product);
        }

        $product->load(['umkmProfile.media', 'category', 'media']);

        // Produk lain DARI LAPAU YANG SAMA — arahkan pengunjung menjelajah
        // etalase usaha itu, bukan berpindah ke usaha lain.
        $terkait = $product->umkmProfile->products()
            ->whereKeyNot($product->getKey())
            ->with(['umkmProfile', 'category', 'media'])
            ->latest()
            ->take(6)
            ->get();

        return view('public.umkm.show', ['nagari' => $nagari, 'product' => $product, 'terkait' => $terkait]);
    }

    /** Detail produk lintas nagari pada domain utama. */
    public function globalShow(Request $request, UmkmProduct $product): View
    {
        $product->loadMissing(['umkmProfile.nagari', 'umkmProfile.media', 'category', 'media']);
        $profile = $product->umkmProfile;
        $nagari = $profile?->nagari;

        abort_unless(
            $nagari?->status === ActiveStatus::Active && $profile?->status === ActiveStatus::Active,
            404
        );

        $seenKey = 'umkm.view.'.sha1($request->ip().'|'.$product->getKey());
        if (Cache::add($seenKey, true, now()->addHours(6))) {
            $product->newQuery()->whereKey($product->getKey())
                ->update(['jumlah_dilihat' => DB::raw('jumlah_dilihat + 1')]);
            UmkmView::catat($product);
        }

        $terkait = $profile->products()
            ->whereKeyNot($product->getKey())
            ->with(['umkmProfile', 'category', 'media'])
            ->latest()
            ->take(6)
            ->get();

        return view('public.umkm.show', [
            'nagari' => $nagari,
            'product' => $product,
            'terkait' => $terkait,
            'global' => true,
        ]);
    }

    /**
     * Produk sebuah lapau beserta filter etalasenya. Kueri yang sama dipakai di
     * domain utama, subdomain nagari, dan fallback lokal agar perilakunya identik.
     *
     * @return array{products: mixed, categoryOptions: mixed, productFilters: array{q: string, category: string}}
     */
    private function etalaseProducts(Request $request, UmkmProfile $profile): array
    {
        $search = trim((string) $request->input('q', ''));
        $categoryId = $request->integer('kategori') ?: null;

        $categoryOptions = UmkmCategory::query()
            ->whereHas('products', fn ($products) => $products
                ->where('umkm_profile_id', $profile->getKey()))
            ->orderBy('nama')
            ->get(['id', 'nama']);

        if ($categoryId && ! $categoryOptions->contains('id', $categoryId)) {
            $categoryId = null;
        }

        $products = $profile->products()
            ->with(['category', 'media'])
            ->when($search !== '', fn ($query) => $query
                ->where('nama_produk', 'like', '%'.$search.'%'))
            ->when($categoryId, fn ($query) => $query
                ->where('umkm_category_id', $categoryId))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return [
            'products' => $products,
            'categoryOptions' => $categoryOptions,
            'productFilters' => [
                'q' => $search,
                'category' => $categoryId ? (string) $categoryId : '',
            ],
        ];
    }
}
