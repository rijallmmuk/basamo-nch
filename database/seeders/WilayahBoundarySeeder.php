<?php

namespace Database\Seeders;

use App\Services\WilayahBoundaryImporter;
use Illuminate\Database\Seeder;

/**
 * Impor geometri batas wilayah Sumbar (cahyadsn) dari database/data/boundaries/
 * ke tabel spasial `wilayah_boundaries`. Idempotent (tabel di-reset tiap impor).
 */
class WilayahBoundarySeeder extends Seeder
{
    public function run(WilayahBoundaryImporter $importer): void
    {
        $dir = database_path('data/boundaries');

        if (! is_dir($dir)) {
            $this->command?->warn("Lewati: $dir tidak ada.");

            return;
        }

        $stats = $importer->import($dir, fn (string $message) => $this->command?->line($message));
        $this->command?->info("Batas wilayah: {$stats['imported']} tersimpan, {$stats['skipped']} dilewati.");
    }
}
