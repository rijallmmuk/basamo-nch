<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\RefWilayah;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Peta publik Sumatera Barat (tanpa login): batas kab/kota (GeoJSON) + jumlah
 * desa terdaftar per kab/kota (choropleth partisipasi).
 */
class PublicMapController extends Controller
{
    public function index(): View
    {
        return view('public.peta');
    }

    /**
     * GeoJSON FeatureCollection kab/kota. Geometri statis di-cache panjang;
     * jumlah desa terdaftar (dinamis) dihitung per request lalu disisipkan.
     */
    public function data(): JsonResponse
    {
        $features = Cache::remember('public.peta.sumbar.geojson', now()->addDay(), fn (): array => $this->buildFeatures());

        $counts = $this->desaCounts();

        foreach ($features as &$feature) {
            $feature['properties']['desa_terdaftar'] = $counts[$feature['properties']['kode']] ?? 0;
        }
        unset($feature);

        return response()
            ->json(['type' => 'FeatureCollection', 'features' => $features])
            ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Bangun fitur GeoJSON kab/kota (geometri + metadata statis, tanpa hitungan).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFeatures(): array
    {
        return RefWilayah::level(RefWilayah::LEVEL_KABUPATEN)
            ->where('kode', 'like', '13.%')
            ->orderBy('nama')
            ->get()
            ->map(fn (RefWilayah $w): array => [
                'type' => 'Feature',
                'properties' => [
                    'kode' => $w->kode,
                    'nama' => $w->nama,
                    'ibukota' => $w->ibukota,
                    'luas' => $w->luas,
                    'penduduk' => $w->penduduk,
                    'logo' => $w->logoUrl(),
                ],
                'geometry' => [
                    'type' => 'MultiPolygon',
                    'coordinates' => self::toMultiPolygon($w->path),
                ],
            ])
            ->all();
    }

    /** @return array<string, int> kode kab/kota => jumlah desa tenant aktif */
    private function desaCounts(): array
    {
        return Desa::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->pluck('wilayah_kode')
            ->groupBy(fn (string $kode): string => self::kabKode($kode))
            ->map->count()
            ->all();
    }

    /**
     * Normalkan `path` Kepmendagri (tak konsisten: satu ring atau banyak ring,
     * urutan [lat,lng]) ke koordinat GeoJSON MultiPolygon (tiap ring = 1 poligon,
     * urutan [lng,lat]).
     *
     * @return array<int, array<int, array<int, array<int, float>>>>
     */
    private static function toMultiPolygon(?string $path): array
    {
        $decoded = json_decode($path ?? '[]', true);

        if (! is_array($decoded) || $decoded === []) {
            return [];
        }

        // Satu ring tunggal ([[lat,lng],…]) → bungkus jadi daftar ring.
        $rings = is_numeric($decoded[0][0] ?? null) ? [$decoded] : $decoded;

        $polygons = [];

        foreach ($rings as $ring) {
            if (! is_array($ring) || count($ring) < 3) {
                continue;
            }

            $coords = array_map(fn (array $point): array => [(float) $point[1], (float) $point[0]], $ring);

            // Tutup ring (syarat GeoJSON) bila titik akhir ≠ titik awal.
            if ($coords[0] !== $coords[count($coords) - 1]) {
                $coords[] = $coords[0];
            }

            $polygons[] = [$coords];
        }

        return $polygons;
    }

    private static function kabKode(string $wilayahKode): string
    {
        $parts = explode('.', $wilayahKode);

        return $parts[0].'.'.($parts[1] ?? '');
    }
}
