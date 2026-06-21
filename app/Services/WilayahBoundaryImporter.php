<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * ETL geometri batas wilayah cahyadsn (database/data/boundaries/*.sql) → tabel
 * spasial `wilayah_boundaries`.
 *
 * Alur: muat tiap berkas SQL ke staging (biar engine DB yang mem-parse, bukan
 * regex), lalu ubah kolom `path` (JSON nested `[lat,lng]`, kedalaman 2–4 tak
 * konsisten) menjadi geometri MariaDB via WKT MULTIPOLYGON + ST_GeomFromText.
 * Sekaligus hasilkan `geom_simplified` (Douglas–Peucker) untuk render peta ringan.
 *
 * Geometri disimpan SRID 0 (kartesian): point-in-polygon planar pada koordinat
 * derajat sudah tepat untuk skala ini, dan menghindari bug urutan-sumbu R-tree
 * MariaDB pada SRID 4326 (ST_Contains via spatial index bisa salah/kosong).
 */
class WilayahBoundaryImporter
{
    private const STAGING = 'wilayah_boundaries_raw';

    /** Hanya impor wilayah Sumatera Barat. */
    private const KODE_PREFIX = '13';

    /** Toleransi Douglas–Peucker (derajat) per level; makin dalam makin halus. */
    private const SIMPLIFY_TOLERANCE = [
        1 => 0.0015,  // provinsi
        2 => 0.0010,  // kab/kota
        3 => 0.0005,  // kecamatan
        4 => 0.0003,  // desa/kelurahan (~30 m)
    ];

    /**
     * @param  callable(string):void|null  $log
     * @return array{imported:int, skipped:int}
     */
    public function import(string $dir, ?callable $log = null): array
    {
        $log ??= fn (string $m) => null;

        $this->createStaging();
        $files = $this->loadSqlFiles($dir, $log);

        if ($files === 0) {
            $this->dropStaging();

            return ['imported' => 0, 'skipped' => 0];
        }

        DB::table('wilayah_boundaries')->delete();

        $imported = 0;
        $skipped = 0;
        $buffer = [];

        DB::table(self::STAGING)
            ->where('kode', 'like', self::KODE_PREFIX.'%')
            ->orderBy('kode')
            ->each(function (object $row) use (&$imported, &$skipped, &$buffer, $log): void {
                $record = $this->toRecord($row);

                if ($record === null) {
                    $skipped++;
                    $log("  ! lewati {$row->kode} (geometri tak valid)");

                    return;
                }

                $buffer[] = $record;

                if (count($buffer) >= 50) {
                    $this->insertBatch($buffer);
                    $imported += count($buffer);
                    $buffer = [];
                }
            });

        if ($buffer !== []) {
            $this->insertBatch($buffer);
            $imported += count($buffer);
        }

        $this->dropStaging();
        $log("Selesai: $imported tersimpan, $skipped dilewati.");

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function createStaging(): void
    {
        DB::statement('DROP TABLE IF EXISTS '.self::STAGING);
        DB::statement(
            'CREATE TABLE '.self::STAGING.' ('
            .'kode VARCHAR(13) PRIMARY KEY, nama VARCHAR(150), '
            .'lat DOUBLE NULL, lng DOUBLE NULL, path LONGTEXT NULL'
            .') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function dropStaging(): void
    {
        DB::statement('DROP TABLE IF EXISTS '.self::STAGING);
    }

    /** @param  callable(string):void  $log */
    private function loadSqlFiles(string $dir, callable $log): int
    {
        $paths = glob(rtrim($dir, '/').'/wilayah_boundaries_*.sql') ?: [];
        sort($paths);

        foreach ($paths as $path) {
            $sql = (string) file_get_contents($path);
            // Berkas menulis ke tabel `wilayah_boundaries`; alihkan ke staging.
            $sql = str_replace('wilayah_boundaries', self::STAGING, $sql);
            DB::unprepared($sql);
            $log('  muat '.basename($path));
        }

        return count($paths);
    }

    /**
     * @return array{kode:string, level:int, parent_kode:string|null, nama:string,
     *               lat:float|null, lng:float|null, wkt:string, wkt_simplified:string|null}|null
     */
    private function toRecord(object $row): ?array
    {
        $polygons = $this->normalize(json_decode((string) $row->path, true));

        if ($polygons === []) {
            return null;
        }

        $wkt = $this->toWkt($polygons);

        if ($wkt === null) {
            return null;
        }

        $level = substr_count((string) $row->kode, '.') + 1;
        $tolerance = self::SIMPLIFY_TOLERANCE[$level] ?? 0.0005;
        $simplifiedPolygons = $this->simplifyPolygons($polygons, $tolerance);
        $wktSimplified = $simplifiedPolygons === [] ? null : $this->toWkt($simplifiedPolygons);

        return [
            'kode' => (string) $row->kode,
            'level' => $level,
            'parent_kode' => str_contains((string) $row->kode, '.')
                ? substr((string) $row->kode, 0, (int) strrpos((string) $row->kode, '.'))
                : null,
            'nama' => (string) $row->nama,
            'lat' => $row->lat !== null ? (float) $row->lat : null,
            'lng' => $row->lng !== null ? (float) $row->lng : null,
            'wkt' => $wkt,
            'wkt_simplified' => $wktSimplified,
        ];
    }

    /**
     * Normalkan `path` cahyadsn ke daftar poligon. Tiap poligon = daftar ring;
     * tiap ring = daftar titik `[lng,lat]` (urutan GeoJSON, dibalik dari sumber).
     * Kedalaman: 2=satu ring, 3=satu poligon (ring+lubang), 4=multipoligon.
     *
     * @return array<int, array<int, array<int, array{0:float,1:float}>>>
     */
    private function normalize(mixed $decoded): array
    {
        if (! is_array($decoded) || $decoded === []) {
            return [];
        }

        $depth = $this->depth($decoded);

        $rawPolygons = match ($depth) {
            2 => [[$decoded]],   // satu ring → satu poligon
            3 => [$decoded],     // satu poligon (ring eksterior + lubang)
            4 => $decoded,       // multipoligon
            default => [],
        };

        $polygons = [];

        foreach ($rawPolygons as $rawRings) {
            if (! is_array($rawRings)) {
                continue;
            }

            $rings = [];

            foreach ($rawRings as $rawRing) {
                $ring = $this->toRing($rawRing);

                if ($ring !== null) {
                    $rings[] = $ring;
                }
            }

            if ($rings !== []) {
                $polygons[] = $rings;
            }
        }

        return $polygons;
    }

    /**
     * Konversi satu ring sumber (`[[lat,lng],…]`) ke `[[lng,lat],…]` yang tertutup.
     * Mengembalikan null bila kurang dari 3 titik unik.
     *
     * @return array<int, array{0:float,1:float}>|null
     */
    private function toRing(mixed $rawRing): ?array
    {
        if (! is_array($rawRing) || count($rawRing) < 3) {
            return null;
        }

        $ring = [];

        foreach ($rawRing as $point) {
            if (is_array($point) && isset($point[0], $point[1]) && is_numeric($point[0]) && is_numeric($point[1])) {
                $ring[] = [(float) $point[1], (float) $point[0]]; // [lat,lng] → [lng,lat]
            }
        }

        if (count($ring) < 3) {
            return null;
        }

        // Tutup ring bila perlu (syarat poligon WKT).
        if ($ring[0] !== $ring[count($ring) - 1]) {
            $ring[] = $ring[0];
        }

        return $ring;
    }

    /** Kedalaman nesting sampai elemen numerik pertama. */
    private function depth(mixed $value): int
    {
        $depth = 0;

        while (is_array($value)) {
            $depth++;
            $value = $value[0] ?? null;
        }

        return $depth;
    }

    /**
     * @param  array<int, array<int, array<int, array{0:float,1:float}>>>  $polygons
     */
    private function toWkt(array $polygons): ?string
    {
        $polygonStrings = [];

        foreach ($polygons as $rings) {
            $ringStrings = [];

            foreach ($rings as $ring) {
                if (count($ring) < 4) {
                    continue;
                }

                $points = array_map(
                    fn (array $p): string => $this->num($p[0]).' '.$this->num($p[1]),
                    $ring
                );
                $ringStrings[] = '('.implode(',', $points).')';
            }

            if ($ringStrings !== []) {
                $polygonStrings[] = '('.implode(',', $ringStrings).')';
            }
        }

        if ($polygonStrings === []) {
            return null;
        }

        return 'MULTIPOLYGON('.implode(',', $polygonStrings).')';
    }

    /**
     * Sederhanakan tiap ring dengan Douglas–Peucker; ring yang menciut (<4 titik)
     * dipertahankan apa adanya agar poligon tetap valid.
     *
     * @param  array<int, array<int, array<int, array{0:float,1:float}>>>  $polygons
     * @return array<int, array<int, array<int, array{0:float,1:float}>>>
     */
    private function simplifyPolygons(array $polygons, float $tolerance): array
    {
        $result = [];

        foreach ($polygons as $rings) {
            $simplifiedRings = [];

            foreach ($rings as $ring) {
                $simplified = $this->douglasPeucker($ring, $tolerance);

                if (count($simplified) < 4) {
                    $simplified = $ring;
                }

                // Pastikan tetap tertutup.
                if ($simplified[0] !== $simplified[count($simplified) - 1]) {
                    $simplified[] = $simplified[0];
                }

                $simplifiedRings[] = $simplified;
            }

            if ($simplifiedRings !== []) {
                $result[] = $simplifiedRings;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array{0:float,1:float}>  $points
     * @return array<int, array{0:float,1:float}>
     */
    private function douglasPeucker(array $points, float $tolerance): array
    {
        $count = count($points);

        if ($count < 3) {
            return $points;
        }

        $first = $points[0];
        $last = $points[$count - 1];
        $maxDistance = 0.0;
        $index = 0;

        for ($i = 1; $i < $count - 1; $i++) {
            $distance = $this->perpendicularDistance($points[$i], $first, $last);

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $index = $i;
            }
        }

        if ($maxDistance <= $tolerance) {
            return [$first, $last];
        }

        $left = $this->douglasPeucker(array_slice($points, 0, $index + 1), $tolerance);
        $right = $this->douglasPeucker(array_slice($points, $index), $tolerance);

        // Buang titik sambungan yang terduplikasi.
        array_pop($left);

        return array_merge($left, $right);
    }

    /**
     * @param  array{0:float,1:float}  $point
     * @param  array{0:float,1:float}  $lineStart
     * @param  array{0:float,1:float}  $lineEnd
     */
    private function perpendicularDistance(array $point, array $lineStart, array $lineEnd): float
    {
        $dx = $lineEnd[0] - $lineStart[0];
        $dy = $lineEnd[1] - $lineStart[1];

        if ($dx === 0.0 && $dy === 0.0) {
            $dx = $point[0] - $lineStart[0];
            $dy = $point[1] - $lineStart[1];

            return sqrt($dx * $dx + $dy * $dy);
        }

        $numerator = abs($dy * $point[0] - $dx * $point[1] + $lineEnd[0] * $lineStart[1] - $lineEnd[1] * $lineStart[0]);
        $denominator = sqrt($dx * $dx + $dy * $dy);

        return $numerator / $denominator;
    }

    private function num(float $value): string
    {
        $formatted = rtrim(rtrim(sprintf('%.8f', $value), '0'), '.');

        return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    private function insertBatch(array $records): void
    {
        $placeholders = [];
        $bindings = [];

        foreach ($records as $record) {
            $placeholders[] = '(?,?,?,?,?,?,ST_GeomFromText(?),'
                .($record['wkt_simplified'] === null ? 'NULL' : 'ST_GeomFromText(?)').')';

            $bindings[] = $record['kode'];
            $bindings[] = $record['level'];
            $bindings[] = $record['parent_kode'];
            $bindings[] = $record['nama'];
            $bindings[] = $record['lat'];
            $bindings[] = $record['lng'];
            $bindings[] = $record['wkt'];

            if ($record['wkt_simplified'] !== null) {
                $bindings[] = $record['wkt_simplified'];
            }
        }

        DB::insert(
            'INSERT INTO wilayah_boundaries '
            .'(kode, level, parent_kode, nama, lat, lng, geom, geom_simplified) VALUES '
            .implode(',', $placeholders),
            $bindings
        );
    }
}
