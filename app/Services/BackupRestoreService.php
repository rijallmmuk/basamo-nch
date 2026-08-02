<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Pencadangan dan pemulihan basis data beserta seluruh berkas tersimpan.
 *
 * Arsip dibuat spatie/laravel-backup dan berisi dua hal: satu dump basis data
 * ter-gzip di `db-dumps/`, dan seluruh isi `storage/app/public` serta
 * `storage/app/private` (tempat berkas materi SLC dan media UMKM) yang disimpan
 * memakai jalur absolut mesin asal.
 *
 * PEMULIHAN bersifat merusak dan tidak dapat dibatalkan, karena itu:
 * - arsip diperiksa dulu keutuhannya dan keberadaan dump-nya;
 * - satu arsip pengaman dibuat OTOMATIS sebelum apa pun ditimpa;
 * - sandi basis data tidak pernah lewat daftar argumen proses, melainkan lewat
 *   berkas defaults sementara yang langsung dihapus;
 * - berkas dipulihkan dengan menimpa, bukan menghapus isi storage lebih dulu,
 *   supaya kegagalan di tengah jalan tidak menghilangkan apa pun.
 */
class BackupRestoreService
{
    /** Entri arsip yang tidak perlu dipulihkan. */
    private const ABAIKAN = ['livewire-tmp/', '/backup-temp/', '/backups/'];

    /** Hanya dua folder ini yang boleh ditulisi saat pemulihan berkas. */
    private const AKAR_DIIZINKAN = ['public/', 'private/'];

    public function disk(): string
    {
        return (string) (config('backup.backup.destination.disks')[0] ?? 'backup_local');
    }

    /**
     * Daftar arsip pada disk backup, terbaru lebih dulu.
     *
     * @return list<array{path: string, nama: string, ukuran: int, dibuat: Carbon}>
     */
    public function daftar(): array
    {
        return collect($this->tujuan()->backups()->toArray())
            ->map(fn (Backup $backup): array => [
                'path' => $backup->path(),
                'nama' => basename($backup->path()),
                'ukuran' => (int) $backup->sizeInBytes(),
                'dibuat' => Carbon::instance($backup->date()->toDateTime()),
            ])
            ->values()
            ->all();
    }

    /** Buat arsip baru berisi basis data dan seluruh berkas. */
    public function buat(): void
    {
        $kode = Artisan::call('backup:run', ['--disable-notifications' => true]);

        if ($kode !== 0) {
            throw new RuntimeException('Pencadangan gagal dijalankan. Periksa log aplikasi.');
        }
    }

    public function hapus(string $path): void
    {
        $backup = $this->cari($path);

        $backup->delete();
    }

    /**
     * Pulihkan dari arsip yang tersimpan di disk backup.
     *
     * @return array{basisData: bool, berkas: int, pengaman: bool}
     */
    public function pulihkan(string $path, bool $buatPengaman = true): array
    {
        $arsip = $this->unduhSementara($path);

        try {
            return $this->pulihkanDariBerkasLokal($arsip, $buatPengaman);
        } finally {
            @unlink($arsip);
        }
    }

    /**
     * Pulihkan dari berkas yang diunggah superadmin. Menerima arsip zip (lengkap,
     * hanya basis data, atau hanya berkas) maupun dump `.sql` / `.sql.gz` polos,
     * sehingga arsip yang tersimpan di komputer sendiri tetap dapat dipakai
     * ketika servernya sudah tidak ada.
     *
     * @return array{basisData: bool, berkas: int, pengaman: bool}
     */
    public function pulihkanDariUnggahan(string $berkasLokal, bool $buatPengaman = true): array
    {
        $nama = mb_strtolower(basename($berkasLokal));

        if (str_ends_with($nama, '.sql') || str_ends_with($nama, '.sql.gz') || str_ends_with($nama, '.gz')) {
            if ($buatPengaman) {
                $this->buat();
            }

            $sqlPath = $this->ekstrakKeSql($berkasLokal, $nama);

            try {
                $this->jalankanDump($sqlPath);
            } finally {
                if ($sqlPath !== $berkasLokal) {
                    @unlink($sqlPath);
                }
            }

            return ['basisData' => true, 'berkas' => 0, 'pengaman' => $buatPengaman];
        }

        return $this->pulihkanDariBerkasLokal($berkasLokal, $buatPengaman);
    }

    /**
     * @return array{basisData: bool, berkas: int, pengaman: bool}
     */
    private function pulihkanDariBerkasLokal(string $arsip, bool $buatPengaman): array
    {
        $zip = $this->buka($arsip);

        try {
            $dump = $this->cariDump($zip, wajib: false);
            $adaBerkas = $this->punyaEntriStorage($zip);

            if ($dump === null && ! $adaBerkas) {
                throw new RuntimeException('Arsip ini tidak memuat dump basis data maupun berkas yang dapat dipulihkan.');
            }

            // Arsip pengaman dibuat SETELAH arsip tujuan terbukti layak, supaya
            // tidak membuang waktu pada arsip yang ternyata rusak.
            if ($buatPengaman) {
                $this->buat();
            }

            if ($dump !== null) {
                $this->pulihkanBasisData($zip, $dump);
            }

            $berkas = $adaBerkas ? $this->pulihkanBerkas($zip) : 0;
        } finally {
            $zip->close();
        }

        return ['basisData' => $dump !== null, 'berkas' => $berkas, 'pengaman' => $buatPengaman];
    }

    /**
     * Aliran arsip lengkap untuk diunduh. Jalurnya WAJIB lewat sini, bukan
     * langsung ke disk: nilai jalur datang dari klien, dan pencarian ini
     * memastikan hanya arsip yang benar-benar terdaftar yang dapat diambil.
     *
     * @return array{nama: string, aliran: resource}
     */
    public function alirkanArsip(string $path): array
    {
        $backup = $this->cari($path);

        return ['nama' => basename($backup->path()), 'aliran' => $backup->stream()];
    }

    /**
     * Dump basis data saja dari sebuah arsip, untuk diunduh terpisah. Ukurannya
     * jauh lebih kecil daripada arsip lengkap yang ikut memuat seluruh berkas.
     *
     * @return array{nama: string, isi: string}
     */
    public function ambilDump(string $path): array
    {
        $arsip = $this->unduhSementara($path);

        try {
            $zip = $this->buka($arsip);

            try {
                $entri = $this->cariDump($zip);
                $sumber = $zip->getStream($entri);

                if ($sumber === false) {
                    throw new RuntimeException('Dump basis data gagal dibaca dari arsip.');
                }

                $keluaran = tempnam(sys_get_temp_dir(), 'basamo-dump-');
                $tujuan = fopen($keluaran, 'wb');
                stream_copy_to_stream($sumber, $tujuan);
                fclose($tujuan);
                fclose($sumber);

                return ['nama' => basename($entri), 'path' => $keluaran];
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($arsip);
        }
    }

    /**
     * Arsip berisi BERKAS SAJA tanpa dump, untuk diunduh terpisah.
     *
     * @return array{nama: string, path: string}
     */
    public function ambilArsipBerkas(string $path): array
    {
        $arsip = $this->unduhSementara($path);
        $keluaran = tempnam(sys_get_temp_dir(), 'basamo-berkas-').'.zip';

        try {
            $sumber = $this->buka($arsip);
            $tujuan = new ZipArchive;

            if ($tujuan->open($keluaran, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Gagal menyiapkan arsip berkas.');
            }

            $tempFiles = [];
            try {
                for ($i = 0; $i < $sumber->numFiles; $i++) {
                    $entri = (string) $sumber->statIndex($i)['name'];

                    if (! $this->entriStorage($entri)) {
                        continue;
                    }

                    $stream = $sumber->getStream($entri);

                    if ($stream !== false) {
                        $tempBerkas = tempnam(sys_get_temp_dir(), 'basamo-isi-');
                        $tempTujuan = fopen($tempBerkas, 'wb');
                        stream_copy_to_stream($stream, $tempTujuan);
                        fclose($tempTujuan);
                        fclose($stream);

                        $tujuan->addFile($tempBerkas, $entri);
                        $tempFiles[] = $tempBerkas;
                    }
                }
            } finally {
                $tujuan->close();
                $sumber->close();
                foreach ($tempFiles as $tf) {
                    @unlink($tf);
                }
            }

            return ['nama' => 'berkas-'.basename($path), 'path' => $keluaran];
        } finally {
            @unlink($arsip);
        }
    }

    private function tujuan(): BackupDestination
    {
        $tujuan = BackupDestination::create($this->disk(), (string) config('backup.backup.name'));

        if (! $tujuan->isReachable()) {
            throw new RuntimeException('Disk backup tidak dapat dijangkau.');
        }

        return $tujuan;
    }

    private function cari(string $path): Backup
    {
        foreach ($this->tujuan()->backups() as $backup) {
            if ($backup->path() === $path) {
                return $backup;
            }
        }

        throw new RuntimeException('Arsip backup tidak ditemukan.');
    }

    /** Salin arsip ke berkas sementara lokal agar dapat dibuka ZipArchive. */
    private function unduhSementara(string $path): string
    {
        $backup = $this->cari($path);
        $sementara = tempnam(sys_get_temp_dir(), 'basamo-pulih-');

        if ($sementara === false) {
            throw new RuntimeException('Gagal menyiapkan berkas sementara.');
        }

        $sumber = $backup->stream();
        $tujuan = fopen($sementara, 'wb');

        if ($tujuan === false) {
            throw new RuntimeException('Gagal membuka berkas sementara.');
        }

        try {
            stream_copy_to_stream($sumber, $tujuan);
        } finally {
            fclose($sumber);
            fclose($tujuan);
        }

        return $sementara;
    }

    private function buka(string $arsip): ZipArchive
    {
        $zip = new ZipArchive;
        $dibuka = $zip->open($arsip, ZipArchive::CHECKCONS);

        if ($dibuka !== true) {
            throw new RuntimeException("Arsip rusak atau tidak dapat dibuka (kode {$dibuka}).");
        }

        $sandi = config('backup.backup.password');

        if (filled($sandi)) {
            $zip->setPassword((string) $sandi);
        }

        return $zip;
    }

    private function cariDump(ZipArchive $zip, bool $wajib = true): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama = (string) $zip->statIndex($i)['name'];

            if (str_starts_with($nama, 'db-dumps/')) {
                return $nama;
            }
        }

        if ($wajib) {
            throw new RuntimeException('Arsip ini tidak memuat dump basis data.');
        }

        return null;
    }

    /** Entri yang benar-benar berkas storage dan layak dipulihkan. */
    private function entriStorage(string $entri): bool
    {
        if (str_starts_with($entri, 'db-dumps/') || str_ends_with($entri, '/')) {
            return false;
        }

        foreach (self::ABAIKAN as $abaikan) {
            if (str_contains($entri, $abaikan)) {
                return false;
            }
        }

        return $this->tujuanAman($entri) !== null;
    }

    /**
     * Jalur tujuan yang AMAN untuk satu entri arsip, atau null bila entri itu
     * tidak boleh ditulis.
     *
     * Isi arsip berasal dari luar: berkas cadangan dapat diunggah superadmin dan
     * isinya tidak tepercaya. Tanpa penjagaan ini sebuah entri berisi `..` dapat
     * mendarat di luar storage, misalnya di `public/`, dan berkas yang tersaji
     * lewat peramban berarti eksekusi kode. Karena itu jalurnya dinormalkan
     * sendiri lalu dipastikan tetap berada di dalam folder yang diizinkan.
     */
    private function tujuanAman(string $entri): ?string
    {
        $posisi = strpos($entri, 'storage/app/');

        if ($posisi === false) {
            return null;
        }

        $relatif = substr($entri, $posisi + strlen('storage/app/'));

        if ($relatif === '' || str_starts_with($relatif, '/') || preg_match('#^[a-zA-Z]:#', $relatif)) {
            return null;
        }

        // Normalkan sendiri: berkas tujuan belum tentu ada, jadi realpath() tidak
        // dapat dipakai untuk memeriksanya lebih dulu.
        $bagian = [];

        foreach (explode('/', str_replace('\\', '/', $relatif)) as $segmen) {
            if ($segmen === '' || $segmen === '.') {
                continue;
            }

            if ($segmen === '..') {
                if (array_pop($bagian) === null) {
                    return null;
                }

                continue;
            }

            $bagian[] = $segmen;
        }

        $bersih = implode('/', $bagian);

        if ($bersih === '') {
            return null;
        }

        foreach (self::AKAR_DIIZINKAN as $akar) {
            if (str_starts_with($bersih, $akar)) {
                return storage_path('app/'.$bersih);
            }
        }

        return null;
    }

    private function punyaEntriStorage(ZipArchive $zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if ($this->entriStorage((string) $zip->statIndex($i)['name'])) {
                return true;
            }
        }

        return false;
    }

    private function pulihkanBasisData(ZipArchive $zip, string $entri): void
    {
        $sumber = $zip->getStream($entri);

        if ($sumber === false) {
            throw new RuntimeException('Dump basis data gagal dibaca dari arsip. Bila terenkripsi, pastikan password sama.');
        }

        $sementara = tempnam(sys_get_temp_dir(), 'basamo-zipdump-');
        $tujuan = fopen($sementara, 'wb');
        stream_copy_to_stream($sumber, $tujuan);
        fclose($tujuan);
        fclose($sumber);

        $sqlPath = $this->ekstrakKeSql($sementara, $entri);

        try {
            $this->jalankanDump($sqlPath);
        } finally {
            if ($sqlPath !== $sementara) {
                @unlink($sqlPath);
            }
            @unlink($sementara);
        }
    }

    private function ekstrakKeSql(string $berkasLokal, string $namaAsal): string
    {
        if (! str_ends_with($namaAsal, '.gz')) {
            return $berkasLokal;
        }

        $sqlPath = tempnam(sys_get_temp_dir(), 'basamo-sql-');

        $sukses = copy("compress.zlib://" . $berkasLokal, $sqlPath);

        if (! $sukses) {
            @unlink($sqlPath);
            throw new RuntimeException('Gagal mengekstrak dump basis data dari bentuk terkompresi.');
        }

        return $sqlPath;
    }

    private function jalankanDump(string $sqlPath): void
    {
        $defaults = tempnam(sys_get_temp_dir(), 'basamo-my-');

        if ($defaults === false) {
            throw new RuntimeException('Gagal menyiapkan berkas sementara pemulihan.');
        }

        try {
            $koneksi = config('database.default');
            $db = config("database.connections.{$koneksi}");

            // Sandi lewat berkas defaults, BUKAN argumen proses: argumen terbaca
            // seluruh pengguna server lewat daftar proses.
            file_put_contents($defaults, implode("\n", [
                '[client]',
                'host='.($db['host'] ?? '127.0.0.1'),
                'port='.($db['port'] ?? 3306),
                'user='.($db['username'] ?? ''),
                'password="'.str_replace('"', '\"', (string) ($db['password'] ?? '')).'"',
                '',
            ]));
            chmod($defaults, 0600);

            $proses = Process::fromShellCommandline(
                'mysql --defaults-extra-file=$DEFAULTS $DATABASE < $DUMP',
                null,
                ['DEFAULTS' => $defaults, 'DATABASE' => $db['database'] ?? '', 'DUMP' => $sqlPath],
                null,
                600,
            );

            $proses->run();

            if (! $proses->isSuccessful()) {
                throw new RuntimeException('Pemulihan basis data gagal: '.trim($proses->getErrorOutput() ?: $proses->getOutput()));
            }

            DB::reconnect();
        } finally {
            @unlink($defaults);
        }
    }

    /**
     * Pulihkan berkas storage dari arsip. Entri disimpan memakai jalur absolut
     * mesin asal, jadi tujuannya ditentukan dari potongan setelah `storage/app/`
     * agar arsip dari server lain tetap dapat dipulihkan.
     */
    private function pulihkanBerkas(ZipArchive $zip): int
    {
        $jumlah = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entri = (string) $zip->statIndex($i)['name'];

            $tujuan = $this->tujuanAman($entri);

            if ($tujuan === null || ! $this->entriStorage($entri)) {
                continue;
            }

            $stream = $zip->getStream($entri);

            if ($stream === false) {
                continue;
            }

            if (! is_dir(dirname($tujuan))) {
                mkdir(dirname($tujuan), 0755, true);
            }

            $out = fopen($tujuan, 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);

            $jumlah++;
        }

        return $jumlah;
    }
}
