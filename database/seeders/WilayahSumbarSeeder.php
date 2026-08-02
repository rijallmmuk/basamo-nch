<?php

namespace Database\Seeders;

use App\Models\RefWilayah;
use Illuminate\Database\Seeder;

/**
 * Impor referensi wilayah Sumatera Barat (kode '13%') dari hasil ekstrak
 * Kepmendagri di database/data/. Idempotent (upsert). Provinsi lain menyusul
 * dengan menambah berkas data + memperluas seeder ini.
 */
class WilayahSumbarSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDaftar();
        $this->seedGeo();
        $this->seedBps();
        $this->seedKodepos();
    }

    /** Daftar kode+nama seluruh level; level & parent diturunkan dari format kode. */
    private function seedDaftar(): void
    {
        $path = database_path('data/sumbar_wilayah.csv');
        if (! is_file($path)) {
            $this->command?->warn("Lewati: $path tidak ada.");

            return;
        }

        $handle = fopen($path, 'r');
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$kode, $nama] = $row;
            $batch[] = [
                'kode' => $kode,
                'nama' => $nama,
                'level' => substr_count($kode, '.') + 1,
                'parent_kode' => str_contains($kode, '.') ? substr($kode, 0, strrpos($kode, '.')) : null,
            ];

            if (count($batch) >= 500) {
                RefWilayah::upsert($batch, ['kode'], ['nama', 'level', 'parent_kode']);
                $batch = [];
            }
        }

        if ($batch !== []) {
            RefWilayah::upsert($batch, ['kode'], ['nama', 'level', 'parent_kode']);
        }

        fclose($handle);
    }

    /** Koordinat/luas/penduduk untuk prov & kab/kota (geometri batas: WilayahBoundarySeeder). */
    private function seedGeo(): void
    {
        $path = database_path('data/sumbar_wilayah_geo.json');
        if (! is_file($path)) {
            return;
        }

        /** @var array<int, array<string, mixed>> $geo */
        $geo = json_decode((string) file_get_contents($path), true) ?? [];

        foreach ($geo as $r) {
            RefWilayah::where('kode', $r['kode'])->update([
                'ibukota' => $r['ibukota'] ?? null,
                'lat' => $r['lat'] ?? null,
                'lng' => $r['lng'] ?? null,
                'luas' => $r['luas'] ?? null,
                'penduduk' => $r['penduduk'] ?? null,
            ]);
        }
    }

    /**
     * Crosswalk kode BPS (beda dari kode Kemendagri kita) — dari ekstrak
     * `db_wilayah_bps.sql`, dipakai nanti utk API eksternal (mis. SDGs Kemendesa
     * yang minta `location_code` format BPS). Hanya UPDATE baris yang sudah ada
     * dari seedDaftar() — TIDAK bikin baris baru (kode tanpa nama/level valid).
     */
    private function seedBps(): void
    {
        $this->updateColumnFromCsv('sumbar_wilayah_bps.csv', 'kode_bps');
    }

    /** Kodepos per kelurahan/desa — dari ekstrak `wilayah_kodepos.sql` (cahyadsn). */
    private function seedKodepos(): void
    {
        $this->updateColumnFromCsv('sumbar_wilayah_kodepos.csv', 'kodepos');
    }

    /** @param  'kode_bps'|'kodepos'  $column */
    private function updateColumnFromCsv(string $filename, string $column): void
    {
        $path = database_path("data/{$filename}");
        if (! is_file($path)) {
            $this->command?->warn("Lewati: $path tidak ada.");

            return;
        }

        $handle = fopen($path, 'r');
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            [$kode, $value] = $row;
            $updated += RefWilayah::where('kode', $kode)->update([$column => $value]);
        }

        fclose($handle);

        $this->command?->info("{$column}: {$updated} baris diperbarui.");
    }
}
