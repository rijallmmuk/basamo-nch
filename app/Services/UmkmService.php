<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;

class UmkmService
{
    /** @var list<string> */
    private const PRODUCT_FIELDS = [
        'umkm_category_id',
        'nama_produk',
        'deskripsi',
        'tautan',
        'harga',
    ];

    /** Batas foto per produk. */
    public const MAX_PHOTOS = 5;

    /**
     * Batas ukuran unggahan MENTAH per foto (KB) — 10 MB per foto (dikompresi & dioptimasi server-side).
     */
    public const MAX_PHOTO_SIZE_KB = 10240;

    /** Sisi terpanjang foto setelah dioptimasi (px). Sama dengan sampul lapak. */
    public const MAX_PHOTO_DIMENSION = 1920;

    /** Mutu WebP hasil kompresi. Sama dengan sampul lapak. */
    public const PHOTO_QUALITY = 82;

    public function grantAccess(User $owner): User
    {
        return DB::transaction(function () use ($owner): User {
            $lockedOwner = User::query()
                ->with('penduduk')
                ->lockForUpdate()
                ->findOrFail($owner->getKey());
            $this->assertEligibleOwner($lockedOwner);

            if ($lockedOwner->umkmProfile()->onlyTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Profil UMKM warga ini sedang diarsipkan. Pulihkan profil tersebut dari menu UMKM.',
                ]);
            }

            $lockedOwner->update(['umkm_access_granted_at' => now()]);

            return $lockedOwner->refresh();
        });
    }

    public function revokeAccess(User $owner): User
    {
        return DB::transaction(function () use ($owner): User {
            $lockedOwner = User::query()->lockForUpdate()->findOrFail($owner->getKey());
            $lockedOwner->update(['umkm_access_granted_at' => null]);

            return $lockedOwner->refresh();
        });
    }

    /**
     * Warga mengisi profil usahanya sendiri setelah akses UMKM diberikan operator.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProfileForGrantedOwner(User $owner, array $data): UmkmProfile
    {
        return DB::transaction(function () use ($owner, $data): UmkmProfile {
            $lockedOwner = User::query()
                ->with('penduduk')
                ->lockForUpdate()
                ->findOrFail($owner->getKey());
            $this->assertEligibleOwner($lockedOwner);

            if (! $lockedOwner->hasUmkmAccess()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Akses UMKM belum diberikan oleh Operator Nagari.',
                ]);
            }

            if ($lockedOwner->umkmProfile()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Warga ini sudah memiliki profil UMKM.',
                ]);
            }

            $profile = $lockedOwner->umkmProfile()->create([
                ...Arr::only($data, [
                    'nama_usaha', 'deskripsi', 'alamat', 'whatsapp', 'email',
                    'jam_operasional', 'tahun_berdiri', 'tautan', 'status',
                ]),
                'nagari_id' => $lockedOwner->nagari_id,
            ]);

            return $profile;
        });
    }

    private function assertEligibleOwner(User $owner): void
    {
        $alasan = $this->eligibilityError($owner);

        if ($alasan !== null) {
            throw ValidationException::withMessages(['user_id' => $alasan]);
        }
    }

    /**
     * Alasan seorang warga BELUM boleh memiliki lapak, atau null bila sudah layak.
     *
     * Sengaja memisahkan tiap sebab: pesan tunggal "identitas tidak aktif" tidak
     * memberi tahu warga maupun operator apa yang harus dibereskan. Dipakai juga
     * oleh halaman pembuatan lapak untuk mencegah form buntu.
     */
    public function eligibilityError(User $owner): ?string
    {
        if (! $owner->hasRole('warga')) {
            return 'Akun ini bukan akun warga, jadi tidak dapat memiliki lapak UMKM.';
        }

        if ($owner->status !== ActiveStatus::Active) {
            return 'Akun warga ini sedang nonaktif.';
        }

        if ($owner->nagari_id === null) {
            return 'Akun warga ini belum terhubung ke nagari mana pun.';
        }

        if ($owner->penduduk === null) {
            return 'Akun ini belum tertaut ke data kependudukan. Minta Operator Nagari menautkan NIK Anda pada menu Data Warga.';
        }

        if ($owner->penduduk->trashed()) {
            return 'Data kependudukan pemilik sedang diarsipkan. Minta Operator Nagari memulihkannya lebih dulu.';
        }

        if ($owner->penduduk->nagari_id !== $owner->nagari_id) {
            return 'Nagari pada akun dan pada data kependudukan berbeda. Minta Operator Nagari menyelaraskannya.';
        }

        return null;
    }

    public function setProfileStatus(UmkmProfile $profile, ActiveStatus $status): UmkmProfile
    {
        $profile->update(['status' => $status]);

        return $profile->refresh();
    }

    /**
     * Tambah produk baru milik profil. Produk tidak punya status terbit: begitu
     * tersimpan ia langsung tampil selama lapaknya aktif.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function createProduct(UmkmProfile $profile, array $data, array $photos = []): UmkmProduct
    {
        $product = $profile->products()->create(Arr::only($data, self::PRODUCT_FIELDS));

        $this->attachPhotos($product, $photos);

        return $product;
    }

    /**
     * Perbarui produk beserta fotonya.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos  Foto baru yang ditambahkan
     * @param  array<int, int>  $removePhotoIds  ID media foto yang dihapus
     */
    public function updateProduct(UmkmProduct $product, array $data, array $photos = [], array $removePhotoIds = []): UmkmProduct
    {
        $product->update(Arr::only($data, self::PRODUCT_FIELDS));

        foreach ($removePhotoIds as $mediaId) {
            $product->media()->where('id', $mediaId)->each(fn ($m) => $m->delete());
        }

        $this->attachPhotos($product, $photos);

        return $product;
    }



    /**
     * Lampirkan foto sambil menghormati batas MAX_PHOTOS (sisa slot saja).
     * Foto dioptimasi dulu sebelum disimpan (pengaman server — umumnya sudah
     * dapat di-resize di klien Filament): sisi terpanjang maks 1920px, WebP q82.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    public function attachPhotos(UmkmProduct $product, array $photos): void
    {
        $remaining = self::MAX_PHOTOS - $product->getMedia('photos')->count();

        Collection::make($photos)
            ->filter()
            ->take(max(0, $remaining))
            ->each(function (UploadedFile $file) use ($product): void {
                $optimizer = app(\App\Services\ImageOptimizer::class);
                // We use MAX_DIMENSION 1600 which is standard in ImageOptimizer, but if they explicitly want 1920:
                $optimizedPath = $optimizer->optimizeToTemp($file->getRealPath(), self::MAX_PHOTO_DIMENSION);

                if ($optimizedPath) {
                    $namaAsli = $file->getClientOriginalName() ?: basename($optimizedPath);

                    try {
                        $product->addMedia($optimizedPath)
                            ->usingFileName(pathinfo($namaAsli, PATHINFO_FILENAME).'.webp')
                            ->toMediaCollection('photos');
                    } finally {
                        @unlink($optimizedPath);
                    }
                } else {
                    $product->addMedia($file)
                        ->toMediaCollection('photos');
                }
            });
    }
}
