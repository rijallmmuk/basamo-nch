<?php

namespace App\Console\Commands;

use App\Services\BackupIntegrityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('ops:verify-backup {--disk= : Disk backup; default memakai disk pertama BACKUP_DISKS} {--max-age=26 : Umur maksimum backup dalam jam}')]
#[Description('Unduh dan periksa konsistensi arsip backup terbaru serta keberadaan dump database')]
class VerifyLatestBackupCommand extends Command
{
    public function handle(BackupIntegrityService $integrity): int
    {
        $disk = (string) ($this->option('disk') ?: config('backup.backup.destination.disks.0'));
        $maximumAge = max(1, (int) $this->option('max-age'));

        try {
            $result = $integrity->verify($disk, $maximumAge);
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup terbaru lolos pemeriksaan integritas.');
        $this->table(['Disk', 'Arsip', 'Umur', 'Dump database'], [[
            $disk,
            $result['path'],
            $result['age_hours'].' jam',
            $result['database_dump'],
        ]]);

        return self::SUCCESS;
    }
}
