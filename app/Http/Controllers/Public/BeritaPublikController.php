<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Enums\KategoriBerita;
use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Nagari;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BeritaPublikController extends Controller
{
    /**
     * Halaman indeks Kabar Nagari untuk satu nagari (subdomain / fallback).
     */
    public function nagari(Request $request, Nagari $nagari): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        return $this->renderIndex($request, $nagari);
    }

    /**
     * Halaman detail satu berita pada konteks nagari.
     */
    public function nagariDetail(Request $request, Nagari $nagari, Berita $berita): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        // Verifikasi bahwa berita memang diperuntukkan bagi nagari ini atau seluruh nagari
        $isVisibleToNagari = $berita->semua_nagari
            || $berita->nagari_id === $nagari->id
            || $berita->nagaris()->where('nagaris.id', $nagari->id)->exists();

        abort_unless($isVisibleToNagari, 404);

        return $this->renderDetail($request, $berita, $nagari);
    }

    /**
     * Halaman indeks Kabar Nagari pada portal induk (global).
     */
    public function global(Request $request): View
    {
        return $this->renderIndex($request, null);
    }

    /**
     * Halaman detail satu berita pada portal induk (global).
     */
    public function globalDetail(Request $request, Berita $berita): View
    {
        return $this->renderDetail($request, $berita, null);
    }

    /**
     * Render daftar berita & pengumuman dengan filter kategori, pencarian, dan penyorot pinned.
     */
    private function renderIndex(Request $request, ?Nagari $nagari = null): View
    {
        $kategoriSlug = $request->query('kategori');
        $search = trim((string) $request->query('cari', ''));
        $filterNagariSlug = $request->query('nagari');

        // Validasi kategori jika diberikan
        $kategori = null;
        if ($kategoriSlug) {
            $kategori = KategoriBerita::tryFrom($kategoriSlug);
        }

        // Query dasar terbitan
        $baseQuery = Berita::query()
            ->published()
            ->with(['nagari', 'creator', 'media']);

        // Filter konteks nagari
        if ($nagari) {
            $baseQuery->forNagari($nagari);
        } elseif ($filterNagariSlug) {
            $selectedNagari = Nagari::where('slug', $filterNagariSlug)->first();
            if ($selectedNagari) {
                $baseQuery->forNagari($selectedNagari);
            }
        }

        // Hitung statistik kategori untuk tab penyaring
        $countQuery = clone $baseQuery;
        $kategoriCounts = [
            'semua' => (clone $countQuery)->count(),
            KategoriBerita::Berita->value => (clone $countQuery)->where('kategori', KategoriBerita::Berita)->count(),
            KategoriBerita::Pengumuman->value => (clone $countQuery)->where('kategori', KategoriBerita::Pengumuman)->count(),
            KategoriBerita::Agenda->value => (clone $countQuery)->where('kategori', KategoriBerita::Agenda)->count(),
        ];

        // Terapkan filter kategori jika dipilih
        if ($kategori) {
            $baseQuery->where('kategori', $kategori);
        }

        // Terapkan pencarian teks jika ada
        if ($search !== '') {
            $baseQuery->where(function (Builder $q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('ringkasan', 'like', "%{$search}%")
                    ->orWhere('konten', 'like', "%{$search}%");
            });
        }

        // Ambil berita sorotan (pinned) untuk halaman pertama jika tidak sedang mencari
        $pinned = null;
        $isFirstPage = (int) $request->query('page', 1) <= 1;

        if ($isFirstPage && empty($search) && empty($kategori)) {
            $pinned = (clone $baseQuery)
                ->where('is_pinned', true)
                ->latest('published_at')
                ->first();
        }

        // Daftar utama berita
        $beritasQuery = clone $baseQuery;
        if ($pinned) {
            $beritasQuery->whereKeyNot($pinned->id);
        }

        $beritas = $beritasQuery
            ->orderBy('is_pinned', 'desc')
            ->latest('published_at')
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        $allNagaris = $nagari ? collect() : Nagari::query()->where('status', ActiveStatus::Active)->orderBy('nama')->get();

        return view('public.berita.index', [
            'nagari' => $nagari,
            'beritas' => $beritas,
            'pinned' => $pinned,
            'kategoriCounts' => $kategoriCounts,
            'currentKategori' => $kategori?->value,
            'search' => $search,
            'currentNagari' => $filterNagariSlug,
            'allNagaris' => $allNagaris,
        ]);
    }

    /**
     * Render halaman detail berita beserta counter pembaca dan berita terkait.
     */
    private function renderDetail(Request $request, Berita $berita, ?Nagari $nagari = null): View
    {
        $user = auth()->user();

        // Cek status terbit; hanya izinkan draf dilihat jika pengguna memiliki izin
        $isPublished = $berita->status === \App\Enums\StatusBerita::Diterbitkan
            && ($berita->published_at === null || $berita->published_at->isPast());

        $canPreview = $user && ($user->isSuperAdmin() || ($user->isOperator() && $user->nagari_id === $berita->nagari_id));

        abort_unless($isPublished || $canPreview, 404);

        // Tambah counter pembaca (satu sesi satu kali hitung)
        $sessionKey = 'viewed_berita_' . $berita->id;
        if (! $request->session()->has($sessionKey)) {
            $berita->increment('views_count');
            $request->session()->put($sessionKey, now()->timestamp);
        }

        // Berita terkait
        $relatedQuery = Berita::query()
            ->published()
            ->whereKeyNot($berita->id)
            ->with(['nagari', 'media']);

        if ($nagari) {
            $relatedQuery->forNagari($nagari);
        } elseif ($berita->nagari_id) {
            $relatedQuery->where('nagari_id', $berita->nagari_id);
        }

        $related = $relatedQuery
            ->where('kategori', $berita->kategori)
            ->latest('published_at')
            ->take(3)
            ->get();

        // Jika kurang dari 3, ambil dari kategori apa saja
        if ($related->count() < 3) {
            $moreQuery = Berita::query()
                ->published()
                ->whereKeyNot($berita->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->with(['nagari', 'media']);

            if ($nagari) {
                $moreQuery->forNagari($nagari);
            }

            $more = $moreQuery->latest('published_at')->take(3 - $related->count())->get();
            $related = $related->concat($more);
        }

        return view('public.berita.show', [
            'nagari' => $nagari,
            'berita' => $berita,
            'related' => $related,
            'isDraftPreview' => ! $isPublished,
        ]);
    }
}
