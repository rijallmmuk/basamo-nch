<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            'panduan' => $profile->category?->panduan_produk,
            'contoh' => $profile->category?->contoh_deskripsi,
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
            'panduan' => $product->umkmProfile?->category?->panduan_produk,
            'contoh' => $product->umkmProfile?->category?->contoh_deskripsi,
        ]);
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
            'nama_produk' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'min:30', 'max:5000'],
            'harga' => ['required', 'integer', 'min:0', 'max:999999999'],
            'photos' => [$creating ? 'required' : 'nullable', 'array', 'max:'.UmkmService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'photos.required' => 'Unggah minimal satu foto produk.',
            'deskripsi.min' => 'Jelaskan produkmu lebih lengkap (minimal 30 karakter) — ikuti panduan di bawah kolom deskripsi.',
        ]);

        $data = [
            'nama_produk' => $validated['nama_produk'],
            'deskripsi' => $validated['deskripsi'],
            'harga' => $validated['harga'],
        ];

        return [$data, $request->file('photos', [])];
    }
}
