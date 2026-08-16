<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Dimensi asli gambar blok materi, untuk dipasang sebagai atribut width & height.
 *
 * Tanpa dimensi, peramban tidak dapat menyediakan ruang bagi gambar yang belum
 * termuat. Seluruh kotak gambar bertinggi nol saat tata letak pertama dihitung,
 * jadi semuanya dianggap berada di dalam layar dan `loading="lazy"` tidak menunda
 * satu pun: halaman berisi sepuluh gambar menarik kesepuluhnya sekaligus. Dimensi
 * juga menghapus lompatan tata letak saat gambar akhirnya muncul.
 *
 * Hasilnya di-cache selamanya karena berkas materi bernama ULID dan tidak pernah
 * ditimpa; berkas yang diganti selalu memakai nama baru.
 */
class UkuranGambarBlok
{
    /** @return array{0: int, 1: int}|null */
    public static function untuk(?string $path): ?array
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $ukuran = Cache::rememberForever(
            'blok-gambar-ukuran:'.sha1($path),
            fn (): array => self::baca($path),
        );

        return count($ukuran) === 2 ? $ukuran : null;
    }

    /** @return array{0: int, 1: int}|array{} */
    private static function baca(string $path): array
    {
        try {
            $disk = Storage::disk(config('slc.material_disk'));

            // Disk jauh (S3 dan sejenisnya) tidak punya path lokal. Biarkan tanpa
            // dimensi daripada menarik seluruh berkas hanya untuk membaca kepalanya.
            if (! method_exists($disk, 'path')) {
                return [];
            }

            $absolut = $disk->path($path);

            if (! is_file($absolut) || ! is_readable($absolut)) {
                return [];
            }

            $info = @getimagesize($absolut);

            if (! is_array($info) || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
                return [];
            }

            return [(int) $info[0], (int) $info[1]];
        } catch (Throwable) {
            return [];
        }
    }
}
