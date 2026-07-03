<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\PengajuanUmkmStatus;
use App\Enums\UmkmProductStatus;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmApplicationDecided;
use App\Notifications\UmkmProductVerified;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

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
        // Query segar (bukan relasi ter-cache) — cegah dobel-create saat instance
        // user yang sama dipakai lintas pemanggilan dengan relasi null yang basi.
        // withTrashed: user_id UNIK (1 warga = 1 lapak) — lapak terarsip dipakai
        // ulang (dipulihkan senyap, TANPA event restored yang menghidupkan akses,
        // karena jalur ini juga dipakai pengajuan yang belum tentu disetujui).
        $profile = $owner->umkmProfile()->withTrashed()->first();

        if ($profile) {
            if ($profile->trashed()) {
                $profile->restoreQuietly();
            }

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
            'alasan_penolakan' => null,
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
            'alasan_penolakan' => $status === UmkmProductStatus::Rejected ? $reason : null,
            'approved_by' => $status === UmkmProductStatus::Approved ? $approverId : null,
            'approved_at' => $status === UmkmProductStatus::Approved ? now() : null,
        ]);

        $product->umkmProfile?->owner?->notify(new UmkmProductVerified($product));

        return $product;
    }

    /**
     * Pengajuan akses UMKM mandiri oleh warga: profil (nonaktif — belum tayang) +
     * satu produk (pending) dalam satu transaksi, lalu beri tahu admin desanya.
     * Ajukan-ulang setelah ditolak memakai profil/produk yang sama (diperbarui).
     *
     * @param  array<string, mixed>  $profilData
     * @param  array<string, mixed>  $productData
     * @param  array<int, UploadedFile>  $photos
     */
    public function submitApplication(User $warga, array $profilData, array $productData, array $photos): UmkmProfile
    {
        $profile = DB::transaction(function () use ($warga, $profilData, $productData, $photos): UmkmProfile {
            $profile = $this->saveProfile($warga, [
                ...$profilData,
                'status' => ActiveStatus::Inactive,
                'status_pengajuan' => PengajuanUmkmStatus::Menunggu,
                'alasan_penolakan_pengajuan' => null,
                'diajukan_at' => now(),
            ]);

            // Ajukan-ulang: perbarui produk pengajuan yang sudah ada; foto lama
            // dipertahankan, foto baru menambah (batas MAX_PHOTOS tetap dihormati).
            if ($product = $profile->products()->first()) {
                $this->updateProduct($product, $productData, $photos);
            } else {
                $this->createProduct($profile, $productData, $photos);
            }

            return $profile;
        });

        // Lonceng panel admin desa (di luar transaksi — kegagalan notif tak membatalkan data).
        if ($admin = $warga->desa?->desaAdmin()->first()) {
            FilamentNotification::make()
                ->title('Pengajuan UMKM baru')
                ->body("{$warga->name} mengajukan lapak \"{$profile->nama_usaha}\". Tinjau di menu Pengajuan UMKM.")
                ->info()
                ->sendToDatabase($admin);
        }

        return $profile;
    }

    /**
     * Setujui pengajuan: akses UMKM aktif, lapak tayang, dan SEMUA produk pending
     * bawaannya ikut disetujui (satu tinjauan cukup — keputusan user 2026-07-02).
     */
    public function approveApplication(UmkmProfile $profile, User $approver): void
    {
        DB::transaction(function () use ($profile, $approver): void {
            $profile->owner?->update(['umkm_access_granted_at' => $profile->owner->umkm_access_granted_at ?? now()]);

            $profile->update([
                'status' => ActiveStatus::Active,
                'status_pengajuan' => null,
                'alasan_penolakan_pengajuan' => null,
            ]);

            // Langsung set (bukan verifyProduct) agar warga dapat SATU notifikasi
            // keputusan pengajuan, bukan dobel dengan notifikasi verifikasi produk.
            $profile->products()
                ->where('status', UmkmProductStatus::Pending)
                ->update([
                    'status' => UmkmProductStatus::Approved,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'alasan_penolakan' => null,
                ]);
        });

        $profile->owner?->notify(new UmkmApplicationDecided($profile, approved: true));
    }

    /**
     * Tolak pengajuan dengan alasan (wajib); warga boleh memperbaiki & mengajukan
     * ulang. Profil tetap nonaktif, produknya tetap pending (tak tayang).
     */
    public function rejectApplication(UmkmProfile $profile, string $reason): void
    {
        $profile->update([
            'status_pengajuan' => PengajuanUmkmStatus::Ditolak,
            'alasan_penolakan_pengajuan' => $reason,
        ]);

        $profile->owner?->notify(new UmkmApplicationDecided($profile, approved: false, reason: $reason));
    }

    /**
     * Lampirkan foto sambil menghormati batas MAX_PHOTOS (sisa slot saja).
     * Foto dioptimasi dulu sebelum disimpan (pengaman server — umumnya sudah
     * dikompres di klien oleh photo-picker): sisi terpanjang maks 1920px, q80.
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
                // File tmp upload tak berekstensi → hasil optimasi disimpan ke
                // path berekstensi agar driver gambar tahu format tujuannya.
                $optimized = $file->getRealPath().'.'.($file->extension() ?: 'jpg');

                Image::load($file->getRealPath())
                    ->fit(Fit::Max, 1920, 1920)
                    ->quality(80)
                    ->save($optimized);

                $product->addMedia($optimized)
                    ->usingFileName($file->getClientOriginalName() ?: basename($optimized))
                    ->toMediaCollection('photos');
            });
    }
}
