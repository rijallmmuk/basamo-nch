<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Throwable;

/**
 * Mengoptimalkan gambar yang diunggah sebelum disimpan agar penyimpanan tidak
 * membengkak: memperkecil dimensi bila melebihi batas & mengubah ke WebP
 * berkualitas. Dipakai untuk gambar materi (blok "gambar") yang volumenya
 * paling banyak. Video = URL eksternal (tanpa penyimpanan); PDF/audio/lampiran
 * tak ditranskode (berisiko) dan cukup dibatasi ukuran di form.
 */
class ImageOptimizer
{
    /** Sisi terpanjang maksimum untuk gambar materi (px) — cukup tajam di layar HP retina. */
    public const MAX_DIMENSION = 1600;

    /** Kualitas WebP — seimbang antara ukuran berkas & ketajaman. */
    public const QUALITY = 80;

    /**
     * Simpan berkas gambar terunggah dalam bentuk teroptimasi (WebP, diperkecil
     * bila perlu) ke disk, kembalikan path relatif tersimpan. Bila optimasi gagal
     * (mis. berkas rusak / format tak didukung GD), simpan berkas asli apa adanya
     * agar unggahan warga/admin tidak hilang.
     */
    public function storeOptimized(
        UploadedFile $file,
        string $disk,
        string $directory,
        int $maxDimension = self::MAX_DIMENSION,
        string $visibility = 'private',
    ): string {
        $directory = trim($directory, '/');
        $path = $directory.'/'.Str::ulid()->toBase32().'.webp';

        try {
            $tempPath = sys_get_temp_dir().'/'.Str::ulid()->toBase32().'.webp';

            // Fit::Max = perkecil agar muat dalam kotak batas TANPA memperbesar
            // gambar kecil (mencegah gambar mungil jadi buram saat ditampilkan).
            Image::load($file->getRealPath())
                ->fit(Fit::Max, $maxDimension, $maxDimension)
                ->quality(self::QUALITY)
                ->save($tempPath);

            Storage::disk($disk)->put($path, file_get_contents($tempPath), $visibility);
            @unlink($tempPath);

            return $path;
        } catch (Throwable $e) {
            report($e);

            $extension = $file->getClientOriginalExtension() ?: 'bin';

            return $file->storeAs(
                $directory,
                Str::ulid()->toBase32().'.'.$extension,
                ['disk' => $disk, 'visibility' => $visibility],
            );
        }
    }

    /**
     * Mengubah gambar ke WebP dan menyimpannya di file sementara (tmp).
     * Pemanggil BERTANGGUNG JAWAB untuk menghapus file tersebut setelah selesai digunakan
     * menggunakan @unlink($path).
     *
     * @return string|null Path sementara file WebP, atau null bila gagal.
     */
    public function optimizeToTemp(
        string $realPath,
        int $maxDimension = self::MAX_DIMENSION,
    ): ?string {
        try {
            $tempPath = sys_get_temp_dir().'/'.Str::ulid()->toBase32().'.webp';

            Image::load($realPath)
                ->fit(Fit::Max, $maxDimension, $maxDimension)
                ->quality(self::QUALITY)
                ->save($tempPath);

            return $tempPath;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Potong gambar tepat ke ukuran banner. Dipakai sebagai pengaman server
     * karena transformasi FilePond di browser tidak selalu tersedia/identik.
     */
    public function cropToTemp(string $realPath, int $width, int $height): ?string
    {
        try {
            $tempPath = sys_get_temp_dir().'/'.Str::ulid()->toBase32().'.webp';

            Image::load($realPath)
                ->fit(Fit::Crop, $width, $height)
                ->quality(self::QUALITY)
                ->save($tempPath);

            return $tempPath;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
