<?php

namespace App\Console\Commands;

use App\Enums\ModuleBlockType;
use App\Models\Materi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Periksa kesehatan berkas media: symlink, berkas yang hilang, dan berkas yang
 * tersimpan tetapi isinya kosong.
 *
 * Gambar yang tidak tampil punya banyak sebab yang gejalanya serupa, dan
 * membedakannya dengan mata sangat sulit: `storage:link` belum dijalankan,
 * berkas tidak pernah sampai ke disk, konversi gagal diam-diam, atau berkasnya
 * ada tetapi terpotong. Perintah ini memisahkan keempatnya.
 */
#[Signature('ops:check-media {--batas=0 : Batasi media publik terbaru; 0 memeriksa semuanya}')]
#[Description('Periksa symlink storage, media publik, dan seluruh berkas materi privat')]
class CheckMediaCommand extends Command
{
    /**
     * Di bawah ini sebuah "gambar" hampir pasti bukan gambar. Berkas WebP
     * terkecil yang masih masuk akal untuk foto pun jauh di atasnya; yang lebih
     * kecil biasanya sisa konversi yang gagal atau unggahan yang terpotong.
     */
    private const AMBANG_MENCURIGAKAN = 2048;

    public function handle(): int
    {
        $bermasalah = $this->periksaSymlink();
        $bermasalah = $this->periksaMediaPublik() || $bermasalah;
        $bermasalah = $this->periksaMateriPrivat() || $bermasalah;

        if ($bermasalah) {
            $this->newLine();
            $this->error('Ada berkas yang bermasalah. Jangan mengosongkan field atau menyimpan ulang materi sebelum storage dipulihkan.');
            $this->line('Pulihkan storage/app/public untuk media dan storage/app/private untuk materi dari backup yang cocok dengan database.');

            return self::FAILURE;
        }

        $this->info('Seluruh media publik dan berkas materi privat yang diperiksa utuh.');

        return self::SUCCESS;
    }

    private function periksaMediaPublik(): bool
    {
        $bermasalah = false;

        $batas = max(0, (int) $this->option('batas'));
        $jumlahMedia = Media::query()->count();

        if ($jumlahMedia === 0) {
            $this->warn('Belum ada media publik tersimpan.');

            return false;
        }

        $query = Media::query()->latest('id');

        if ($batas > 0) {
            $query->limit($batas);
        }

        $jumlahDiperiksa = $batas > 0 ? min($jumlahMedia, $batas) : $jumlahMedia;

        $baris = [];

        foreach ($query->cursor() as $item) {
            $path = $item->getPath();
            $nyata = is_file($path) ? filesize($path) : null;

            $catatan = match (true) {
                $nyata === null => 'BERKAS HILANG',
                $nyata === 0 => 'KOSONG',
                $nyata < self::AMBANG_MENCURIGAKAN => 'TERLALU KECIL',
                (int) $item->size !== $nyata => 'BEDA DENGAN CATATAN DB',
                default => 'ok',
            };

            if ($catatan !== 'ok') {
                $bermasalah = true;
            }

            // Konversi yang tercatat sudah dibuat tetapi berkasnya tidak ada
            // adalah penyebab tersembunyi paling sering: aslinya utuh, yang
            // ditampilkan halaman justru konversinya.
            $konversiRusak = [];

            foreach (array_keys($item->generated_conversions ?? []) as $konversi) {
                $jalurKonversi = $item->getPath($konversi);

                if (! is_file($jalurKonversi)) {
                    $konversiRusak[] = $konversi.': hilang';
                } elseif (filesize($jalurKonversi) < self::AMBANG_MENCURIGAKAN) {
                    $konversiRusak[] = $konversi.': '.filesize($jalurKonversi).' B';
                }
            }

            if ($konversiRusak !== []) {
                $bermasalah = true;
            }

            if ($catatan !== 'ok' || $konversiRusak !== []) {
                $baris[] = [
                    $item->id,
                    $item->collection_name,
                    $item->mime_type,
                    number_format((int) $item->size, 0, ',', '.'),
                    $nyata === null ? '-' : number_format($nyata, 0, ',', '.'),
                    $catatan,
                    $konversiRusak === [] ? 'ok' : implode(', ', $konversiRusak),
                ];
            }
        }

        if ($baris !== []) {
            $this->table(
                ['ID', 'Koleksi', 'Jenis', 'Ukuran DB', 'Ukuran Nyata', 'Catatan', 'Konversi'],
                $baris,
            );
        } else {
            $this->info("Media publik: {$jumlahDiperiksa} berkas utuh.");
        }

        if ($bermasalah) {
            $this->error('Ada media publik yang bermasalah. Arti tiap catatan:');
            $this->line('  BERKAS HILANG    barisnya ada di basis data, berkasnya tidak ada di disk');
            $this->line('  KOSONG / KECIL   berkas tersimpan tetapi isinya bukan gambar utuh;');
            $this->line('                   biasanya rantai pengoptimal gambar gagal, atau `gd`');
            $this->line('                   tidak mendukung WebP. Lihat DEPLOY.md langkah 1.');
            $this->line('  Konversi hilang  aslinya utuh, tetapi turunan yang dipakai halaman gagal dibuat.');
            $this->line('Pulihkan berkas publik dari backup setelah penyebabnya dibereskan.');
        }

        return $bermasalah;
    }

    private function periksaMateriPrivat(): bool
    {
        $disk = Storage::disk(config('slc.material_disk'));
        $baris = [];
        $jumlahBerkas = 0;

        foreach (Materi::query()->select(['id', 'module_id', 'judul', 'blocks'])->cursor() as $materi) {
            foreach (array_values($materi->blocks ?? []) as $index => $block) {
                $typeValue = $block['type'] ?? null;
                $type = is_string($typeValue) ? ModuleBlockType::tryFrom($typeValue) : null;

                if (! $type?->storesFile()) {
                    continue;
                }

                $jumlahBerkas++;
                $path = $block['data']['file'] ?? null;
                $catatan = 'ok';
                $ukuran = null;

                if (! is_string($path) || trim($path) === '') {
                    $catatan = 'REFERENSI KOSONG';
                } elseif (! Materi::isSafeBlockFilePath($type, $path)) {
                    $catatan = 'PATH TIDAK SAH';
                } elseif (! $disk->exists($path)) {
                    $catatan = 'BERKAS HILANG';
                } else {
                    $ukuran = $disk->size($path);

                    if ($ukuran === 0) {
                        $catatan = 'KOSONG';
                    }
                }

                if ($catatan !== 'ok') {
                    $baris[] = [
                        $materi->id,
                        $materi->module_id,
                        $index + 1,
                        $type->getLabel(),
                        is_string($path) && $path !== '' ? $path : '-',
                        $ukuran === null ? '-' : number_format($ukuran, 0, ',', '.'),
                        $catatan,
                    ];
                }
            }
        }

        if ($jumlahBerkas === 0) {
            $this->warn('Belum ada berkas materi privat yang direferensikan.');

            return false;
        }

        if ($baris === []) {
            $this->info("Berkas materi privat: {$jumlahBerkas} referensi utuh.");

            return false;
        }

        $this->newLine();
        $this->error(count($baris)." dari {$jumlahBerkas} referensi berkas materi privat bermasalah:");
        $this->table(
            ['Materi', 'Modul', 'Blok', 'Tipe', 'Path', 'Ukuran', 'Catatan'],
            $baris,
        );
        $this->line('REFERENSI KOSONG berarti JSON materi kehilangan path; BERKAS HILANG berarti path masih tercatat tetapi file privat tidak ada.');

        return true;
    }

    private function periksaSymlink(): bool
    {
        $tautan = public_path('storage');

        if (is_link($tautan)) {
            $target = readlink($tautan);

            if (! is_dir($target)) {
                $this->error("public/storage menunjuk {$target}, tetapi folder itu tidak ada.");

                return true;
            }

            $this->info("public/storage symlink ke {$target}");

            return false;
        }

        if (is_dir($tautan)) {
            // Terjadi bila `storage:link` gagal lalu foldernya dibuat manual.
            // Berkas baru akan tersimpan di storage/app/public sedangkan yang
            // dilayankan folder kosong ini, sehingga SETIAP gambar 404.
            $this->error('public/storage adalah folder biasa, bukan symlink.');
            $this->line('Hapus foldernya lalu jalankan: php artisan storage:link');

            return true;
        }

        $this->error('public/storage tidak ada. Jalankan: php artisan storage:link');

        return true;
    }
}
