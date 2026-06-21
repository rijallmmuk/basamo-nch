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
                'elv' => $r['elv'] ?? null,
                'tz' => $r['tz'] ?? null,
                'luas' => $r['luas'] ?? null,
                'penduduk' => $r['penduduk'] ?? null,
            ]);
        }
    }
}
