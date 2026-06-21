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
 * Peta publik Sumatera Barat (tanpa login): batas kab/kota + jumlah desa
 * terdaftar di platform per kab/kota (choropleth partisipasi).
 */
class PublicMapController extends Controller
{
    public function index(): View
    {
        return view('public.peta');
    }

    public function data(): JsonResponse
    {
        $features = Cache::remember('public.peta.sumbar', now()->addHour(), function (): array {
            // Jumlah desa tenant aktif per kab/kota (dari kode wilayah desa).
            $counts = Desa::query()
                ->where('status', ActiveStatus::Active)
                ->whereNotNull('wilayah_kode')
                ->pluck('wilayah_kode')
                ->groupBy(fn (string $kode) => self::kabKode($kode))
                ->map->count();

            return RefWilayah::level(RefWilayah::LEVEL_KABUPATEN)
                ->where('kode', 'like', '13.%')
                ->orderBy('nama')
                ->get()
                ->map(fn (RefWilayah $w): array => [
                    'kode' => $w->kode,
                    'nama' => $w->nama,
                    'ibukota' => $w->ibukota,
                    'luas' => $w->luas,
                    'penduduk' => $w->penduduk,
                    'lat' => $w->lat,
                    'lng' => $w->lng,
                    'logo' => $w->logoUrl(),
                    'desa_terdaftar' => $counts[$w->kode] ?? 0,
                    'path' => json_decode($w->path ?? '[]', true),
                ])
                ->all();
        });

        return response()->json($features);
    }

    private static function kabKode(string $wilayahKode): string
    {
        $parts = explode('.', $wilayahKode);

        return $parts[0].'.'.($parts[1] ?? '');
    }
}
