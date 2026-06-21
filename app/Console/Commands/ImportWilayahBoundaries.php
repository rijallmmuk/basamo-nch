<?php

namespace App\Console\Commands;

use App\Services\WilayahBoundaryImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wilayah:import-boundaries {--dir= : Folder berkas .sql (default database/data/boundaries)}')]
#[Description('Impor geometri batas wilayah (cahyadsn) ke tabel spasial wilayah_boundaries')]
class ImportWilayahBoundaries extends Command
{
    public function handle(WilayahBoundaryImporter $importer): int
    {
        $dir = $this->option('dir') ?: database_path('data/boundaries');

        if (! is_dir($dir)) {
            $this->error("Folder tidak ditemukan: $dir");

            return self::FAILURE;
        }

        $this->info("Impor batas wilayah dari $dir …");
        $stats = $importer->import($dir, fn (string $message) => $this->line($message));

        $this->info("Tersimpan: {$stats['imported']}  ·  Dilewati: {$stats['skipped']}");

        return self::SUCCESS;
    }
}
