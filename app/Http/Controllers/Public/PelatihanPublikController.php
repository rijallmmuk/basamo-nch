<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\Pelatihan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Halaman muka sebuah pelatihan, terbuka untuk umum.
 *
 * Yang boleh dilihat tanpa masuk: judul, tema, sampul, ringkasan, siapa
 * pengajarnya, dan DAFTAR JUDUL modulnya. Yang tidak: isi materi, berkas
 * lampiran, pre-test, evaluasi, dan diskusi. Halaman ini adalah etalase, bukan
 * pintu belakang; batas itu ditegakkan dengan tidak pernah memuat isinya sama
 * sekali, bukan sekadar menyembunyikannya di tampilan.
 *
 * Penjaga kelihatannya SAMA PERSIS dengan katalog: `ready()` (punya modul yang
 * sudah siap) ditambah cakupan nagari. Menyalin aturannya dengan sedikit berbeda
 * akan membuat pelatihan yang tak muncul di katalog tetap bisa dibuka lewat
 * alamat langsung.
 */
class PelatihanPublikController extends Controller
{
    public function nagari(Nagari $nagari, Pelatihan $pelatihan): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        $pelatihan = $this->pastikanTampil(
            fn (Builder $query) => $query->forNagari($nagari->getKey()),
            $pelatihan,
        );

        return view('public.slc.pelatihan', [
            'pelatihan' => $pelatihan,
            'nagari' => $nagari,
        ]);
    }

    public function global(Pelatihan $pelatihan): View
    {
        $pelatihan = $this->pastikanTampil(
            fn (Builder $query) => $query->withPublicAudience(),
            $pelatihan,
        );

        return view('public.slc.pelatihan', [
            'pelatihan' => $pelatihan,
            'nagari' => null,
        ]);
    }

    /**
     * Ambil ulang pelatihan lewat penjaga katalog, atau 404.
     *
     * Sengaja dibaca ulang alih-alih memeriksa model yang sudah terikat rute:
     * hasilnya satu kueri yang tak mungkin menyimpang dari aturan katalog, dan
     * modul yang ikut termuat pun sudah tersaring `ready()` sejak awal.
     */
    private function pastikanTampil(callable $cakupan, Pelatihan $pelatihan): Pelatihan
    {
        $query = Pelatihan::query()->ready()->whereKey($pelatihan->getKey());

        $cakupan($query);

        return $query
            ->with([
                'tema',
                'media',
                'creator:id,name,lembaga,nagari_id',
                'creator.roles',
                'creator.nagari:id,nama',
                'pengajars:id,name,lembaga,nagari_id',
                'pengajars.roles',
                'pengajars.nagari:id,nama',
                // Judul modul saja. Materi, berkas, dan evaluasinya TIDAK ikut:
                // yang tidak dimuat tidak bisa bocor.
                'modules' => fn ($modules) => $modules
                    ->ready()
                    ->with('media')
                    ->orderBy('urutan')
                    ->orderBy('id'),
            ])
            ->withCount(['modules' => fn ($modules) => $modules->ready()])
            ->firstOrFail();
    }
}
