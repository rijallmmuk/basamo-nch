<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UmkmProductController extends Controller
{
    public function __construct(private readonly UmkmService $umkm) {}

    public function create(): View|RedirectResponse
    {
        $profile = auth()->user()->umkmProfile;

        if (! $profile) {
            return redirect()->route('portal.umkm.profile.edit')
                ->with('info', 'Lengkapi profil usaha dulu sebelum menambah produk.');
        }

        return view('portal.umkm.product-form', [
            'product' => null,
            'namaUsaha' => $profile->nama_usaha,
            ...$this->kategoriViewData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = auth()->user()->umkmProfile;
        abort_unless($profile, 403);

        [$data, $photos] = $this->validated($request, creating: true);

        $this->umkm->createProduct($profile, $data, $photos);

        return redirect()->route('portal.umkm.index')
            ->with('success', 'Produk diajukan dan menunggu verifikasi Admin '.(auth()->user()->desa?->jenisDesa?->nama ?? 'Desa').'.');
    }

    public function edit(UmkmProduct $product): View
    {
        $this->authorize('update', $product);

        return view('portal.umkm.product-form', [
            'product' => $product->load('media'),
            'namaUsaha' => $product->umkmProfile?->nama_usaha ?? '',
            ...$this->kategoriViewData(),
        ]);
    }

    /**
     * Kategori milik PRODUK (pola marketplace): dropdown kategori + bantuan
     * deskripsi (panduan & contoh) yang mengikuti kategori terpilih secara live.
     *
     * @return array{kategori: array<int, string>, panduanMap: Collection<int, string>, contohMap: Collection<int, string>}
     */
    private function kategoriViewData(): array
    {
        return [
            'kategori' => UmkmCategory::options(),
            'panduanMap' => UmkmCategory::whereNotNull('panduan_produk')->pluck('panduan_produk', 'id'),
            'contohMap' => UmkmCategory::whereNotNull('contoh_deskripsi')->pluck('contoh_deskripsi', 'id'),
        ];
    }

    public function update(Request $request, UmkmProduct $product): RedirectResponse
    {
        $this->authorize('update', $product);

        [$data, $photos] = $this->validated($request);

        $removePhotoIds = collect($request->input('remove_photos', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        // Produk katalog wajib punya minimal satu foto — hitung sisa setelah
        // penghapusan (hanya ID foto milik produk ini yang dihitung) + unggahan baru.
        $fotoMilik = $product->getMedia('photos')->pluck('id');
        $sisaFoto = $fotoMilik->count()
            - $fotoMilik->intersect($removePhotoIds)->count()
            + count($photos);

        if ($sisaFoto < 1) {
            return back()->withInput()
                ->withErrors(['photos' => 'Produk wajib punya minimal satu foto — jangan hapus semuanya.']);
        }

        // Jangan buang kelebihan foto diam-diam — tolak dengan pesan jelas.
        if ($sisaFoto > UmkmService::MAX_PHOTOS) {
            return back()->withInput()
                ->withErrors(['photos' => 'Maksimal '.UmkmService::MAX_PHOTOS.' foto per produk — hapus sebagian foto lama dulu.']);
        }

        $this->umkm->updateProduct($product, $data, $photos, $removePhotoIds);

        return redirect()->route('portal.umkm.index')
            ->with('success', 'Produk diperbarui dan menunggu verifikasi ulang.');
    }

    public function destroy(UmkmProduct $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('portal.umkm.index')
            ->with('success', 'Produk dihapus.');
    }

    /**
     * Aturan field produk = aturan produk pengajuan (keputusan user 2026-07-02):
     * deskripsi, harga, dan foto wajib — foto minimal 1 saat membuat; saat mengubah,
     * kecukupan foto dihitung di update() (foto lama − dihapus + baru ≥ 1).
     *
     * @return array{0: array<string, mixed>, 1: array<int, UploadedFile>}
     */
    private function validated(Request $request, bool $creating = false): array
    {
        $validated = $request->validate([
            'umkm_category_id' => ['required', 'integer', 'exists:umkm_categories,id'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'min:30', 'max:5000'],
            'harga' => ['required', 'integer', 'min:0', 'max:999999999'],
            'photos' => [$creating ? 'required' : 'nullable', 'array', 'max:'.UmkmService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:10240'],
        ], [
            'photos.required' => 'Unggah minimal satu foto produk.',
            'deskripsi.min' => 'Jelaskan produkmu lebih lengkap (minimal 30 karakter) — ikuti panduan di bawah kolom deskripsi.',
        ]);

        $data = [
            'umkm_category_id' => $validated['umkm_category_id'],
            'nama_produk' => $validated['nama_produk'],
            'deskripsi' => $validated['deskripsi'],
            'harga' => $validated['harga'],
        ];

        return [$data, $request->file('photos', [])];
    }
}
