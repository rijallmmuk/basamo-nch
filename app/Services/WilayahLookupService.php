<?php

namespace App\Services;

use App\Models\RefWilayah;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian & resolusi data wilayah (ref_wilayah) untuk form Nagari — diekstrak dari
 * App\Filament\Resources\Nagaris\Schemas\NagariForm (masih dipakai Filament) supaya
 * panel /kelola (migrasi superadmin, Fase 1) bisa memakai pencarian yang sama.
 */
class WilayahLookupService
{
    /**
     * Hasil pencarian desa/nagari resmi: "Nama · Kecamatan, Kabupaten — Kode". Ditulis
     * portable lintas-driver (dulu pakai SUBSTRING_INDEX/SOUNDS LIKE mentah — MySQL/
     * MariaDB only, gagal di SQLite yang dipakai test suite): join kec/kab dihitung di
     * PHP (kode wilayah dot-separated), SOUNDS LIKE hanya dipasang bila driver mendukung.
     *
     * @return array<string, string> kode => label
     */
    public function search(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        // Angka/titik = kode wilayah (mis. "13.06") → jangan cocokkan fonetik,
        // SOUNDEX atas angka hanya menghasilkan derau.
        $isKode = (bool) preg_match('/^[\d.]+$/', $search);
        $soundsLike = ! $isKode && DB::connection()->getDriverName() === 'mysql';

        $rows = DB::table('ref_wilayah as d')
            ->where('d.level', RefWilayah::LEVEL_DESA)
            // Cocok pada NAMA atau KODE; nama juga toleran ejaan mirip/serupa
            // via SOUNDS LIKE (mis. "koto tua" → "Koto Tuo", "balenka" → "Balingka").
            ->where(fn (Builder $q): Builder => $q
                ->where('d.nama', 'like', "%{$search}%")
                ->orWhere('d.kode', 'like', "%{$search}%")
                ->when($soundsLike, fn (Builder $w): Builder => $w->orWhereRaw('d.nama sounds like ?', [$search])))
            // Kecocokan langsung (diawali teks, lalu mengandung teks) di atas;
            // kemiripan fonetik menyusul di bawah.
            ->orderByRaw('(d.nama like ? or d.kode like ?) desc', ["{$search}%", "{$search}%"])
            ->orderByRaw('(d.nama like ? or d.kode like ?) desc', ["%{$search}%", "%{$search}%"])
            ->orderBy('d.nama')
            ->limit(50)
            ->get(['d.kode', 'd.nama']);

        $ancestorKodes = $rows->flatMap(fn (object $r): array => [$this->ancestor($r->kode, 3), $this->ancestor($r->kode, 2)])
            ->filter()
            ->unique();

        $ancestorNama = RefWilayah::whereIn('kode', $ancestorKodes)->pluck('nama', 'kode');

        return $rows->mapWithKeys(function (object $r) use ($ancestorNama): array {
            $kec = $ancestorNama->get($this->ancestor($r->kode, 3));
            $kab = $ancestorNama->get($this->ancestor($r->kode, 2));

            return [$r->kode => "{$r->nama} · {$kec}, {$kab} — {$r->kode}"];
        })->all();
    }

    /**
     * Data resmi utk kode wilayah terpilih (dipakai server-side saat simpan — TIDAK
     * mempercayai field tersembunyi dari klien, beda dari pola Filament lama).
     *
     * @return array{nama: string, provinsi: ?string, kabupaten: ?string, kecamatan: ?string, koordinat_lat: ?float, koordinat_lng: ?float}|null
     */
    public function resolve(string $kode): ?array
    {
        $desa = RefWilayah::find($kode);

        if (! $desa) {
            return null;
        }

        $geo = DB::table('wilayah_boundaries')->where('kode', $kode)->first(['lat', 'lng']);

        return [
            'nama' => $desa->nama,
            'provinsi' => RefWilayah::find($this->ancestor($kode, 1))?->nama,
            'kabupaten' => RefWilayah::find($this->ancestor($kode, 2))?->nama,
            'kecamatan' => RefWilayah::find($this->ancestor($kode, 3))?->nama,
            'koordinat_lat' => $geo?->lat,
            'koordinat_lng' => $geo?->lng,
        ];
    }

    /** Kode leluhur pada `n` segmen pertama (1=prov, 2=kab, 3=kec). */
    private function ancestor(string $kode, int $segments): ?string
    {
        $parts = explode('.', $kode);

        return count($parts) >= $segments ? implode('.', array_slice($parts, 0, $segments)) : null;
    }
}
