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
        if (! auth()->user()->umkmProfile) {
            return redirect()->route('portal.umkm.profile.edit')
                ->with('info', 'Lengkapi profil usaha dulu sebelum menambah produk.');
        }

        return view('portal.umkm.product-form', ['product' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = auth()->user()->umkmProfile;
        abort_unless($profile, 403);

        [$data, $photos] = $this->validated($request);

        $this->umkm->createProduct($profile, $data, $photos);

        return redirect()->route('portal.umkm.index')
            ->with('success', 'Produk diajukan dan menunggu verifikasi Admin Nagari.');
    }

    public function edit(UmkmProduct $product): View
    {
        $this->authorize('update', $product);

        return view('portal.umkm.product-form', [
            'product' => $product->load('media'),
        ]);
    }

    public function update(Request $request, UmkmProduct $product): RedirectResponse
    {
        $this->authorize('update', $product);

        [$data, $photos] = $this->validated($request);

        $removePhotoIds = collect($request->input('remove_photos', []))
            ->map(fn ($id) => (int) $id)
            ->all();

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
     * @return array{0: array<string, mixed>, 1: array<int, UploadedFile>}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'nama_produk' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'harga' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'photos' => ['nullable', 'array', 'max:'.UmkmService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'nama_produk' => $validated['nama_produk'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga' => $validated['harga'] ?? null,
        ];

        return [$data, $request->file('photos', [])];
    }
}
