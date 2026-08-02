<?php

namespace Database\Seeders;

use App\Services\WilayahBoundaryImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Impor geometri batas wilayah Sumbar (cahyadsn) dari database/data/boundaries/
 * ke tabel spasial `wilayah_boundaries`. Idempotent (tabel di-reset tiap impor).
 * Butuh fungsi spasial MySQL/MariaDB (ST_GeomFromText) — dilewati di driver lain.
 */
class WilayahBoundarySeeder extends Seeder
{
    public function run(WilayahBoundaryImporter $importer): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->command?->warn("Lewati impor batas wilayah: butuh MySQL/MariaDB (driver: {$driver}).");

            return;
        }

        $dir = database_path('data/boundaries');

        if (! is_dir($dir)) {
            $this->command?->warn("Lewati: $dir tidak ada.");

            return;
        }

        $stats = $importer->import($dir, fn (string $message) => $this->command?->line($message));
        $this->command?->info("Batas wilayah: {$stats['imported']} tersimpan, {$stats['skipped']} dilewati.");
    }
}
