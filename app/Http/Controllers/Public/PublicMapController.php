<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\RefWilayah;
use App\Services\WilayahBoundaryService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Data peta publik (GeoJSON) — petanya sendiri ditanam di section #peta beranda.
 * Drill-down dua tingkat:
 *  - tanpa parameter  → batas kab/kota (choropleth jumlah nagari terdaftar);
 *  - ?kab=13.01       → batas nagari/kelurahan di dalam kab tsb (tandai terdaftar).
 *
 * Geometri diambil dari tabel spasial `wilayah_boundaries` (ST_AsGeoJSON merakit
 * GeoJSON langsung di DB, presisi dipangkas ~1 m). Geometri statis di-cache panjang;
 * status terdaftar (dinamis) disisipkan per request.
 */
class PublicMapController extends Controller
{
    /** Presisi koordinat GeoJSON (desimal derajat) — 5 ≈ 1 m, cukup untuk peta web. */
    private const COORD_PRECISION = 5;

    public function data(Request $request): JsonResponse
    {
        $kab = (string) $request->query('kab', '');

        return preg_match('/^13\.\d{2}$/', $kab) === 1
            ? $this->nagariData($kab)
            : $this->kabData();
    }

    /** FeatureCollection kab/kota + jumlah nagari terdaftar per kab/kota. */
    private function kabData(): JsonResponse
    {
        $features = Cache::remember(
            'peta.kab.geojson.v3.'.WilayahBoundaryService::cacheVersion(),
            now()->addDay(),
            fn (): array => $this->buildKabFeatures()
        );

        $counts = $this->nagariCountsPerKab();

        foreach ($features as &$feature) {
            $feature['properties']['nagari_terdaftar'] = $counts[$feature['properties']['kode']] ?? 0;
        }
        unset($feature);

        return $this->collection($features);
    }

    /** FeatureCollection nagari/kelurahan dalam satu kab/kota + tanda terdaftar. */
    private function nagariData(string $kab): JsonResponse
    {
        $features = Cache::remember(
            'peta.nagari.'.$kab.'.geojson.'.WilayahBoundaryService::cacheVersion(),
            now()->addDay(),
            fn (): array => $this->buildNagariFeatures($kab)
        );

        $registered = $this->registeredNagariUrls();

        foreach ($features as &$feature) {
            $feature['properties']['terdaftar'] = $registered->has($feature['properties']['kode']);
            $feature['properties']['url'] = $registered->get($feature['properties']['kode']);
        }
        unset($feature);

        return $this->collection($features);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildKabFeatures(): array
    {
        $rows = $this->geometryRows(fn ($query) => $query->where('level', RefWilayah::LEVEL_KABUPATEN));

        $refs = RefWilayah::whereIn('kode', $rows->pluck('kode'))->get()->keyBy('kode');

        return $rows->map(function (object $row) use ($refs): array {
            $ref = $refs->get($row->kode);

            return $this->feature($row, [
                'kode' => $row->kode,
                'nama' => $row->nama,
                'ibukota' => $ref?->ibukota,
                'luas' => $ref?->luas,
                'penduduk' => $ref?->penduduk,
                // Path RELATIF — cache berumur sehari tak boleh mengunci host
                // (pernah ter-cache http://127.0.0.1:8001 → logo mati di host lain).
                'logo' => $ref?->logoUrl() ? parse_url($ref->logoUrl(), PHP_URL_PATH) : null,
            ]);
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buildNagariFeatures(string $kab): array
    {
        $rows = $this->geometryRows(fn ($query) => $query
            ->where('level', RefWilayah::LEVEL_DESA)
            ->where('kode', 'like', $kab.'.%'));

        return $rows->map(fn (object $row): array => $this->feature($row, [
            'kode' => $row->kode,
            'nama' => $row->nama,
        ]))->all();
    }

    /**
     * Baris geometri + GeoJSON dari DB (pakai versi tersederhana bila ada).
     *
     * @param  callable(Builder):Builder  $scope
     * @return Collection<int, object>
     */
    private function geometryRows(callable $scope): Collection
    {
        $query = DB::table('wilayah_boundaries')
            ->orderBy('kode')
            ->select('kode', 'nama', DB::raw(
                'ST_AsGeoJSON(COALESCE(geom_simplified, geom), '.self::COORD_PRECISION.') as geojson'
            ));

        return $scope($query)->get();
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function feature(object $row, array $properties): array
    {
        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($row->geojson, true),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $features
     */
    private function collection(array $features): JsonResponse
    {
        return response()
            ->json(['type' => 'FeatureCollection', 'features' => $features])
            ->header('Cache-Control', 'public, max-age=300');
    }

    /** @return array<string, int> kode kab/kota => jumlah nagari tenant aktif */
    private function nagariCountsPerKab(): array
    {
        return Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->pluck('wilayah_kode')
            ->groupBy(fn (string $kode): string => $this->kabKode($kode))
            ->map->count()
            ->all();
    }

    /** @return Collection<string, string> kode nagari mitra aktif → URL situs subdomainnya */
    private function registeredNagariUrls(): Collection
    {
        return Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->get(['id', 'slug', 'wilayah_kode'])
            ->mapWithKeys(fn (Nagari $nagari): array => [
                $nagari->wilayah_kode => route('public.nagari.home', $nagari),
            ]);
    }

    private function kabKode(string $wilayahKode): string
    {
        $parts = explode('.', $wilayahKode);

        return $parts[0].'.'.($parts[1] ?? '');
    }
}
