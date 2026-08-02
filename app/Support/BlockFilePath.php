<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Storage;

/**
 * Penjaga path berkas untuk unggahan yang bersarang di dalam blok materi.
 *
 * `preventFilePathTampering()` bawaan Filament mengesahkan sebuah path dengan
 * mencocokkannya ke `getOriginalFilePaths()`, yang membaca ATRIBUT MODEL bernama
 * sama dengan field. Itu bekerja untuk unggahan biasa (mis. `sampul` pada Nagari),
 * tetapi TIDAK untuk unggahan di dalam Builder: field-nya bernama `file`, sedangkan
 * model `Materi` tidak punya atribut `file` — berkasnya tersimpan di dalam array
 * JSON `blocks`.
 *
 * Akibatnya daftar path asli selalu kosong sehingga path apa pun ditolak, dan itu
 * memunculkan dua gejala sekaligus: berkas gagal disimpan ("berisi path berkas yang
 * tidak diizinkan") sekaligus tidak pernah tampil saat form Edit dibuka.
 *
 * Penjaga ini menggantikan pencocokan tersebut dengan syarat yang tetap ketat:
 * path harus persis satu berkas di dalam direktori yang ditetapkan komponen, dan
 * berkas itu harus benar-benar ada di disk. Dengan begitu path karangan, penelusuran
 * direktori (`../`), maupun berkas di luar direktori tetap ditolak.
 */
class BlockFilePath
{
    /** @return Closure(string): bool */
    public static function allow(string $disk, string $directory): Closure
    {
        $directory = trim($directory, '/');

        return static function (string $file) use ($disk, $directory): bool {
            $file = ltrim($file, '/');

            // Harus berada TEPAT satu tingkat di dalam direktori komponen. Ini
            // sekaligus menutup `../` karena nama berkas tak boleh mengandung garis
            // miring maupun titik ganda.
            $awalan = $directory.'/';

            if (! str_starts_with($file, $awalan)) {
                return false;
            }

            $nama = substr($file, strlen($awalan));

            if ($nama === '' || str_contains($nama, '/') || str_contains($nama, '..')) {
                return false;
            }

            return Storage::disk($disk)->exists($file);
        };
    }
}
