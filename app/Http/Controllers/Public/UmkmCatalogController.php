<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
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
 * produknya, lalu detail produk. Tujuannya menjadikan tiap profil UMKM "rumah"
 * pemiliknya. Yang tampil hanya lapak aktif dari nagari aktif; seluruh produk
 * lapak itu ikut tampil (produk tidak punya status terbit).
 */
class UmkmCatalogController extends Controller
{
    /** Direktori UMKM global lintas nagari aktif, dengan filter nagari. */
    public function globalDirectory(Request $request): View
    {
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
            ->when($request->filled('q'), fn ($query) => $query
                ->where('nama_usaha', 'like', '%'.trim((string) $request->input('q')).'%'))
            ->orderByDesc('produk_count')
            ->orderBy('nama_usaha')
            ->paginate(24)
            ->withQueryString();

        return view('public.umkm.directory', [
            'nagari' => null,
            'usaha' => $usaha,
            'nagariOptions' => $nagariOptions,
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'nagari' => $selectedNagariId ? (string) $selectedNagariId : '',
            ],
        ]);
    }

    /** Direktori UMKM satu nagari: daftar usaha aktif + jumlah produknya. */
    public function directory(Request $request, Nagari $nagari): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        $usaha = UmkmProfile::query()
            ->where('nagari_id', $nagari->id)
            ->where('status', ActiveStatus::Active)
            ->with('media')
            ->withCount('products as produk_count')
            ->when($request->filled('q'), fn ($q) => $q->where('nama_usaha', 'like', '%'.trim((string) $request->input('q')).'%'))
            ->orderByDesc('produk_count')
            ->orderBy('nama_usaha')
            ->paginate(24)
            ->withQueryString();

        return view('public.umkm.directory', [
            'nagari' => $nagari,
            'usaha' => $usaha,
            'nagariOptions' => collect(),
            'filters' => [
                'q' => (string) $request->input('q', ''),
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
            'products' => $profile->products()->with(['category', 'media'])->latest()->paginate(24)->withQueryString(),
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

        $products = $profile->products()
            ->with(['category', 'media'])
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('public.umkm.etalase', [
            'nagari' => $nagari,
            'profile' => $profile,
            'products' => $products,
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

        // Produk lain DARI TOKO YANG SAMA (memperkuat konsep "rumah" pemilik) —
        // arahkan pengunjung menjelajah etalase usaha itu, bukan lintas toko.
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
}
