<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\RefWilayah;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Peta publik Sumatera Barat (tanpa login). Drill-down dua tingkat:
 *  - tanpa parameter  → batas kab/kota (choropleth jumlah desa terdaftar);
 *  - ?kab=13.01       → batas desa/kelurahan di dalam kab tsb (tandai terdaftar).
 *
 * Geometri diambil dari tabel spasial `wilayah_boundaries` (ST_AsGeoJSON merakit
 * GeoJSON langsung di DB, presisi dipangkas ~1 m). Geometri statis di-cache panjang;
 * status terdaftar (dinamis) disisipkan per request.
 */
class PublicMapController extends Controller
{
    /** Presisi koordinat GeoJSON (desimal derajat) — 5 ≈ 1 m, cukup untuk peta web. */
    private const COORD_PRECISION = 5;

    public function index(): View
    {
        return view('public.peta');
    }

    public function data(Request $request): JsonResponse
    {
        $kab = (string) $request->query('kab', '');

        return preg_match('/^13\.\d{2}$/', $kab) === 1
            ? $this->desaData($kab)
            : $this->kabData();
    }

    /** FeatureCollection kab/kota + jumlah desa terdaftar per kab/kota. */
    private function kabData(): JsonResponse
    {
        $features = Cache::remember(
            'peta.kab.geojson',
            now()->addDay(),
            fn (): array => $this->buildKabFeatures()
        );

        $counts = $this->desaCountsPerKab();

        foreach ($features as &$feature) {
            $feature['properties']['desa_terdaftar'] = $counts[$feature['properties']['kode']] ?? 0;
        }
        unset($feature);

        return $this->collection($features);
    }

    /** FeatureCollection desa/kelurahan dalam satu kab/kota + tanda terdaftar. */
    private function desaData(string $kab): JsonResponse
    {
        $features = Cache::remember(
            "peta.desa.$kab.geojson",
            now()->addDay(),
            fn (): array => $this->buildDesaFeatures($kab)
        );

        $registered = $this->registeredDesaKodes();

        foreach ($features as &$feature) {
            $feature['properties']['terdaftar'] = $registered->has($feature['properties']['kode']);
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
                'logo' => $ref?->logoUrl(),
            ]);
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buildDesaFeatures(string $kab): array
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

    /** @return array<string, int> kode kab/kota => jumlah desa tenant aktif */
    private function desaCountsPerKab(): array
    {
        return Desa::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->pluck('wilayah_kode')
            ->groupBy(fn (string $kode): string => $this->kabKode($kode))
            ->map->count()
            ->all();
    }

    /** @return Collection<string, string> set kode desa tenant aktif */
    private function registeredDesaKodes(): Collection
    {
        return Desa::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->pluck('wilayah_kode')
            ->keyBy(fn (string $kode): string => $kode);
    }

    private function kabKode(string $wilayahKode): string
    {
        $parts = explode('.', $wilayahKode);

        return $parts[0].'.'.($parts[1] ?? '');
    }
}
