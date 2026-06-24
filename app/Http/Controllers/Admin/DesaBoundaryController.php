<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\PublicMapController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * GeoJSON batas wilayah desa milik admin yang sedang login — sumber data peta di
 * halaman Pengaturan Desa. Geometri statis di-cache panjang & di-fetch klien
 * (payload Livewire tetap ringan). Pola mengikuti {@see PublicMapController}.
 */
class DesaBoundaryController extends Controller
{
    /** Presisi koordinat GeoJSON (desimal derajat) — 5 ≈ 1 m, cukup untuk peta web. */
    private const COORD_PRECISION = 5;

    /** Fallback pusat peta: tengah Sumatera Barat (bila batas/koordinat tak ada). */
    private const FALLBACK_LAT = -0.74;

    private const FALLBACK_LNG = 100.6;

    public function show(): JsonResponse
    {
        $user = auth()->user();

        // Ter-scope ketat: hanya admin desa, hanya desanya sendiri (tanpa parameter → tanpa IDOR).
        abort_unless(($user?->isDesaAdmin() ?? false) && $user->desa, 403);

        $desa = $user->desa;

        $boundary = $desa->wilayah_kode
            ? Cache::remember(
                "desa.boundary.{$desa->wilayah_kode}",
                now()->addDay(),
                fn (): array => $this->loadBoundary($desa->wilayah_kode),
            )
            : ['lat' => null, 'lng' => null, 'geometry' => null];

        $lat = $desa->koordinat_lat ?? $boundary['lat'] ?? self::FALLBACK_LAT;
        $lng = $desa->koordinat_lng ?? $boundary['lng'] ?? self::FALLBACK_LNG;

        return response()
            ->json([
                'nama' => $desa->nama_lengkap,
                'center' => [(float) $lat, (float) $lng],
                'geometry' => $boundary['geometry'],
            ])
            ->header('Cache-Control', 'private, max-age=300');
    }

    /**
     * Muat batas wilayah dari tabel spasial (versi tersederhana bila ada).
     *
     * @return array{lat: float|null, lng: float|null, geometry: mixed}
     */
    private function loadBoundary(string $kode): array
    {
        $row = DB::table('wilayah_boundaries')
            ->where('kode', $kode)
            ->selectRaw('lat, lng, ST_AsGeoJSON(COALESCE(geom_simplified, geom), '.self::COORD_PRECISION.') as geojson')
            ->first();

        return [
            'lat' => $row->lat ?? null,
            'lng' => $row->lng ?? null,
            'geometry' => $row && $row->geojson ? json_decode($row->geojson, true) : null,
        ];
    }
}
