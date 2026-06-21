<?php

namespace App\Services;

use App\Enums\UmkmProductStatus;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmProductVerified;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UmkmService
{
    /** Batas foto per produk. */
    public const MAX_PHOTOS = 5;

    /**
     * Buat atau perbarui profil usaha milik pemilik. Desa mengikuti pemilik.
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
            'desa_id' => $owner->desa_id,
        ]);
    }

    /**
     * Tambah produk baru milik profil. Selalu mulai berstatus pending (menunggu
     * verifikasi Admin Desa).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function createProduct(UmkmProfile $profile, array $data, array $photos = []): UmkmProduct
    {
        $product = $profile->products()->create([
            ...$data,
            'status' => UmkmProductStatus::Pending,
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
            'status' => UmkmProductStatus::Pending,
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
     * Verifikasi produk oleh admin (setujui/tolak). Menyetel jejak verifikasi lalu
     * memberi tahu pemilik. Dipakai antrian verifikasi global & relation manager.
     */
    public function verifyProduct(UmkmProduct $product, UmkmProductStatus $status, ?int $approverId = null, ?string $reason = null): UmkmProduct
    {
        $product->update([
            'status' => $status,
            'rejection_reason' => $status === UmkmProductStatus::Rejected ? $reason : null,
            'approved_by' => $status === UmkmProductStatus::Approved ? $approverId : null,
            'approved_at' => $status === UmkmProductStatus::Approved ? now() : null,
        ]);

        $product->umkmProfile->owner?->notify(new UmkmProductVerified($product));

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
