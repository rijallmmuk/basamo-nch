<?php

namespace App\Services;

use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UmkmService
{
    /** Batas foto per produk. */
    public const MAX_PHOTOS = 5;

    /**
     * Buat atau perbarui profil usaha milik pemilik. Nagari mengikuti pemilik.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveProfile(User $owner, array $data): UmkmProfile
    {
        $profile = $owner->umkmProfile;

        if ($profile) {
            $profile->update($data);

            return $profile;
        }

        return $owner->umkmProfile()->create([
            ...$data,
            'nagari_id' => $owner->nagari_id,
        ]);
    }

    /**
     * Tambah produk baru milik profil. Selalu mulai berstatus pending (menunggu
     * verifikasi Admin Nagari).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function createProduct(UmkmProfile $profile, array $data, array $photos = []): UmkmProduct
    {
        $product = $profile->products()->create([
            ...$data,
            'status' => 'pending',
        ]);

        $this->attachPhotos($product, $photos);

        return $product;
    }

    /**
     * Perbarui produk. Setiap perubahan mengembalikan status ke pending agar
     * diverifikasi ulang (cegah konten lolos verifikasi diubah diam-diam).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos  Foto baru yang ditambahkan
     * @param  array<int, int>  $removePhotoIds  ID media foto yang dihapus
     */
    public function updateProduct(UmkmProduct $product, array $data, array $photos = [], array $removePhotoIds = []): UmkmProduct
    {
        $product->update([
            ...$data,
            'status' => 'pending',
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        foreach ($removePhotoIds as $mediaId) {
            $product->media()->where('id', $mediaId)->each(fn ($m) => $m->delete());
        }

        $this->attachPhotos($product, $photos);

        return $product;
    }

    /**
     * Lampirkan foto sambil menghormati batas MAX_PHOTOS (sisa slot saja).
     *
     * @param  array<int, UploadedFile>  $photos
     */
    public function attachPhotos(UmkmProduct $product, array $photos): void
    {
        $remaining = self::MAX_PHOTOS - $product->getMedia('photos')->count();

        Collection::make($photos)
            ->filter()
            ->take(max(0, $remaining))
            ->each(fn (UploadedFile $file) => $product->addMedia($file)->toMediaCollection('photos'));
    }
}
