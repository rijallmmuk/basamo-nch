<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Enums\StatusIdm;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\RefWilayah;
use App\Services\PublicTerasAggregateService;
use App\Services\WilayahBoundaryService;
use App\Support\PublicNavigation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Data peta publik (GeoJSON) untuk halaman peta penuh Teras Nagari.
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

    public function __construct(private readonly PublicTerasAggregateService $aggregate) {}

    /**
     * Titik nagari mitra untuk tampilan awal Teras. Endpoint ini sengaja tidak
     * membawa demografi rinci, cuaca, atau deret sensor; data berat baru diminta
     * setelah satu nagari dipilih.
     */
    public function mitra(): JsonResponse
    {
        $nagaris = $this->aggregate->performaNagari()->map(function (Nagari $nagari): array {
            $statusIdm = $nagari->status_idm
                ? (StatusIdm::tryFrom((string) $nagari->status_idm)?->label() ?? (string) $nagari->status_idm)
                : null;

            return [
                'slug' => $nagari->slug,
                'nama' => $nagari->nama_lengkap,
                'kabupaten' => $nagari->kabupaten,
                'kecamatan' => $nagari->kecamatan,
                'kode_wilayah' => $nagari->wilayah_kode,
                // GeoJSON selalu [longitude, latitude]. Null berarti nagari tetap
                // tersedia di daftar, tetapi tidak boleh ditumpuk pada titik rekaan.
                'koordinat' => $nagari->koordinat_lat !== null && $nagari->koordinat_lng !== null
                    ? [(float) $nagari->koordinat_lng, (float) $nagari->koordinat_lat]
                    : null,
                'url_detail' => route('public.teras.nagari.data', $nagari),
                'url_bagikan' => route('public.teras', ['nagari' => $nagari->slug]),
                'url_teras' => PublicNavigation::rute('public.nagari.teras', $nagari),
                'ringkasan' => [
                    'penduduk' => (int) $nagari->total_penduduk,
                    'umkm' => (int) $nagari->total_umkm,
                    'produk' => (int) $nagari->total_produk,
                    'warga_belajar' => (int) $nagari->warga_belajar,
                    'modul_selesai' => (int) $nagari->modul_selesai,
                    'titik_iot' => (int) $nagari->total_iot,
                    'sdgs' => (int) $nagari->sdg_terisi > 0 ? round((float) $nagari->skor_sdgs, 1) : null,
                    'idm' => $statusIdm,
                    'cuaca_terdaftar' => filled($nagari->wilayah_kode),
                ],
            ];
        })->values();

        return response()
            ->json(['data' => $nagaris])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Seluruh batas nagari sebagai lapisan dasar statis. Status kemitraan tidak
     * disisipkan di sini agar geometri besar dapat di-cache lama; peramban
     * menggabungkannya dengan endpoint mitra yang selalu real-time.
     */
    public function batasNagari(): JsonResponse
    {
        $features = Cache::remember(
            'peta.semua-nagari.geojson.v1.'.WilayahBoundaryService::cacheVersion(),
            now()->addDay(),
            fn (): array => $this->buildAllNagariFeatures(),
        );

        return response()
            ->json(['type' => 'FeatureCollection', 'features' => $features])
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /** FeatureCollection batas seluruh kabupaten/kota di Sumatera Barat. */
    public function batasKabupaten(): JsonResponse
    {
        return $this->kabData();
    }

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

        $registered = $this->registeredNagaris();

        foreach ($features as &$feature) {
            $nagari = $registered->get($feature['properties']['kode']);
            $feature['properties']['terdaftar'] = $nagari !== null;
            $feature['properties']['slug'] = $nagari['slug'] ?? null;
            $feature['properties']['url'] = $nagari['url'] ?? null;
            $feature['properties']['url_detail'] = $nagari['url_detail'] ?? null;
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

    /** @return array<int, array<string, mixed>> */
    private function buildAllNagariFeatures(): array
    {
        $rows = $this->geometryRows(fn ($query) => $query->where('level', RefWilayah::LEVEL_DESA));

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
        // SQLite dipakai untuk audit lokal dan tidak menyediakan fungsi spasial.
        // Titik nagari tetap dapat tampil; lapisan batas wilayah cukup dilewati.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect();
        }

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

    /** @return Collection<string, array{slug: string, url: string, url_detail: string}> */
    private function registeredNagaris(): Collection
    {
        return Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->get(['id', 'slug', 'wilayah_kode'])
            ->mapWithKeys(fn (Nagari $nagari): array => [
                $nagari->wilayah_kode => [
                    'slug' => $nagari->slug,
                    'url' => route('public.nagari.home', $nagari),
                    'url_detail' => route('public.teras.nagari.data', $nagari),
                ],
            ]);
    }

    private function kabKode(string $wilayahKode): string
    {
        $parts = explode('.', $wilayahKode);

        return $parts[0].'.'.($parts[1] ?? '');
    }
}
