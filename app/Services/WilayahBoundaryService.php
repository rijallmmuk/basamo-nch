<?php

namespace App\Services;

use App\Models\Nagari;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use JsonException;

class WilayahBoundaryService
{
    public const CACHE_VERSION_KEY = 'wilayah.boundary.cache_version';

    private const COORD_PRECISION = 5;

    private const FALLBACK_LAT = -0.74;

    private const FALLBACK_LNG = 100.6;

    /**
     * @return array{
     *     nama: string,
     *     center: array{0: float, 1: float},
     *     geometry: array<string, mixed>|null,
     *     boundaryAvailable: bool,
     *     centerSource: 'record'|'boundary'|'fallback'
     * }
     */
    public function forNagari(Nagari $nagari): array
    {
        $boundary = $nagari->wilayah_kode
            ? Cache::remember(
                'nagari.boundary.'.self::cacheVersion().'.'.$nagari->wilayah_kode,
                now()->addDay(),
                fn (): array => $this->loadBoundary($nagari->wilayah_kode),
            )
            : ['lat' => null, 'lng' => null, 'geometry' => null];

        $lat = $nagari->koordinat_lat ?? $boundary['lat'] ?? self::FALLBACK_LAT;
        $lng = $nagari->koordinat_lng ?? $boundary['lng'] ?? self::FALLBACK_LNG;
        $centerSource = match (true) {
            $nagari->koordinat_lat !== null && $nagari->koordinat_lng !== null => 'record',
            $boundary['lat'] !== null && $boundary['lng'] !== null => 'boundary',
            default => 'fallback',
        };

        return [
            'nama' => $nagari->nama_lengkap,
            'center' => [(float) $lat, (float) $lng],
            'geometry' => $boundary['geometry'],
            'boundaryAvailable' => $boundary['geometry'] !== null,
            'centerSource' => $centerSource,
        ];
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    /** @return array{lat: float|null, lng: float|null, geometry: array<string, mixed>|null} */
    private function loadBoundary(string $kode): array
    {
        $query = DB::table('wilayah_boundaries')->where('kode', $kode);
        $isSpatialDatabase = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        $row = $isSpatialDatabase
            ? $query
                ->selectRaw('lat, lng, ST_AsGeoJSON(COALESCE(geom_simplified, geom), '.self::COORD_PRECISION.') as geojson')
                ->first()
            : $query->first(['lat', 'lng']);

        return [
            'lat' => $row->lat ?? null,
            'lng' => $row->lng ?? null,
            'geometry' => $this->decodeGeometry($row->geojson ?? null),
        ];
    }

    /** @return array<string, mixed>|null */
    private function decodeGeometry(?string $geojson): ?array
    {
        if (! $geojson) {
            return null;
        }

        try {
            $geometry = json_decode($geojson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($geometry) && isset($geometry['type'], $geometry['coordinates'])
            ? $geometry
            : null;
    }
}
