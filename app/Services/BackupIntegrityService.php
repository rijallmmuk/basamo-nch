<?php

namespace App\Services;

use RuntimeException;
use Spatie\Backup\BackupDestination\BackupDestination;
use ZipArchive;

class BackupIntegrityService
{
    /**
     * @return array{path: string, size: float, age_hours: int, database_dump: string}
     */
    public function verify(string $disk, int $maximumAgeHours): array
    {
        $destination = BackupDestination::create($disk, (string) config('backup.backup.name'));

        if (! $destination->isReachable()) {
            throw new RuntimeException("Disk backup {$disk} tidak dapat dijangkau.");
        }

        $backup = $destination->newestBackup();

        if ($backup === null) {
            throw new RuntimeException("Belum ada backup pada disk {$disk}.");
        }

        $ageHours = (int) $backup->date()->diffInHours(now(), absolute: true);

        if ($ageHours > $maximumAgeHours) {
            throw new RuntimeException("Backup terbaru berumur {$ageHours} jam; batas {$maximumAgeHours} jam.");
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'basamo-backup-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Gagal membuat file sementara untuk verifikasi backup.');
        }

        try {
            $source = $backup->stream();
            $target = fopen($temporaryPath, 'wb');

            if ($target === false) {
                throw new RuntimeException('Gagal membuka file sementara backup.');
            }

            try {
                stream_copy_to_stream($source, $target);
            } finally {
                fclose($source);
                fclose($target);
            }

            $zip = new ZipArchive;
            $opened = $zip->open($temporaryPath, ZipArchive::CHECKCONS);

            if ($opened !== true) {
                throw new RuntimeException("Arsip backup rusak atau tidak dapat dibuka (kode {$opened}).");
            }

            try {
                if (filled(config('backup.backup.password'))) {
                    $zip->setPassword((string) config('backup.backup.password'));
                }

                $databaseDump = $this->databaseDumpEntry($zip);
            } finally {
                $zip->close();
            }

            return [
                'path' => $backup->path(),
                'size' => $backup->sizeInBytes(),
                'age_hours' => $ageHours,
                'database_dump' => $databaseDump,
            ];
        } finally {
            @unlink($temporaryPath);
        }
    }

    private function databaseDumpEntry(ZipArchive $zip): string
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (! is_array($stat)) {
                continue;
            }

            $name = (string) ($stat['name'] ?? '');
            $size = (int) ($stat['size'] ?? 0);

            if (! (str_ends_with($name, '.sql') || str_ends_with($name, '.sql.gz')) || $size <= 0) {
                continue;
            }

            $stream = $zip->getStream($name);

            if ($stream === false) {
                throw new RuntimeException('Dump database tidak dapat didekripsi atau dibaca.');
            }

            try {
                $header = fread($stream, 64);
            } finally {
                fclose($stream);
            }

            if (! is_string($header) || $header === '') {
                throw new RuntimeException('Dump database kosong setelah dibuka.');
            }

            if (str_ends_with($name, '.sql.gz') && ! str_starts_with($header, "\x1f\x8b")) {
                throw new RuntimeException('Dump database gzip memiliki header yang tidak valid.');
            }

            return $name;
        }

        throw new RuntimeException('Arsip tidak memiliki dump database yang valid.');
    }
}
