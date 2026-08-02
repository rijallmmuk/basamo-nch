<?php

namespace App\Support\Dashboard;

use App\Enums\JenisKelamin;
use App\Models\Penduduk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Sebaran kependudukan untuk dasbor operator (satu nagari) dan superadmin/DPMD
 * (lintas nagari). `$nagariId` null berarti seluruh nagari.
 *
 * Sumbernya tabel `penduduk`, BUKAN `users`: penduduk mencakup seluruh warga yang
 * terdata, sedangkan users hanya yang punya akun. Keduanya tidak boleh dicampur
 * dalam satu angka.
 *
 * Seluruh pengelompokan dikerjakan basis data. Satu nagari bisa berisi puluhan ribu
 * baris penduduk, jadi tidak ada yang boleh ditarik ke memori hanya untuk dihitung.
 */
class DemografiData
{
    /** Batas bawah tiap kelompok umur; kelompok terakhir terbuka ke atas. */
    private const KELOMPOK_UMUR = [0, 5, 15, 25, 35, 45, 55, 65];

    /**
     * Piramida penduduk: kelompok umur di sumbu tegak, laki-laki ke kiri dan
     * perempuan ke kanan. Sengaja menggantikan donat jenis kelamin karena memuat
     * pembagian yang sama sekaligus menunjukkan bentuk usianya.
     *
     * @return array<string, mixed>
     */
    public static function piramidaUmur(?int $nagariId = null): array
    {
        $laki = self::hitungUmur($nagariId, JenisKelamin::LakiLaki->value);
        $perempuan = self::hitungUmur($nagariId, JenisKelamin::Perempuan->value);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 380,
                'stacked' => true,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'plotOptions' => [
                'bar' => ['horizontal' => true, 'barHeight' => '75%', 'borderRadius' => 3],
            ],
            'series' => [
                // Sisi kiri dibuat negatif supaya batangnya menjulur berlawanan arah;
                // labelnya dikembalikan positif lewat pengaturan sumbu di bawah.
                ['name' => 'Laki-laki', 'data' => array_map(fn (int $n): int => -$n, $laki)],
                ['name' => 'Perempuan', 'data' => array_values($perempuan)],
            ],
            'xaxis' => [
                'categories' => self::labelUmur(),
                // Sisi kiri negatif dikembalikan jadi angka positif oleh formatter
                // di extraJsOptions() widgetnya; opsi PHP tidak dapat memuat fungsi.
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => ['labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']]],
            'colors' => ['#0284c7', '#ec4899'],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'bottom', 'labels' => ['fontFamily' => 'inherit']],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }

    /**
     * Sebaran pendidikan terakhir. Dipakai menentukan bahasa dan bentuk materi
     * pelatihan, jadi kelompok "belum terdata" pun ikut ditampilkan apa adanya.
     *
     * @return array<string, mixed>
     */
    public static function pendidikan(?int $nagariId = null): array
    {
        return self::batangReferensi(
            self::hitungReferensi($nagariId, 'pendidikan', 'pendidikan_id'),
            '#003857',
        );
    }

    /**
     * Sebaran pekerjaan. Ditampilkan sepuluh terbanyak saja; daftar acuan pekerjaan
     * OpenSID panjang dan ekornya tidak menambah keputusan apa pun.
     *
     * @return array<string, mixed>
     */
    public static function pekerjaan(?int $nagariId = null): array
    {
        return self::batangReferensi(
            self::hitungReferensi($nagariId, 'pekerjaan', 'pekerjaan_id')->take(10),
            '#d4ac0d',
        );
    }

    /**
     * Penduduk yang tanggal lahirnya belum terisi tidak dapat dikelompokkan menurut
     * umur dan otomatis hilang dari piramida. Jumlahnya dilaporkan supaya operator
     * tahu grafiknya belum mewakili semua orang, bukan mengira nagarinya memang
     * sekecil itu.
     */
    public static function tanpaTanggalLahir(?int $nagariId = null): int
    {
        return self::dasar($nagariId)->whereNull('tanggal_lahir')->count();
    }

    /** @return list<string> */
    private static function labelUmur(): array
    {
        $label = [];

        foreach (self::KELOMPOK_UMUR as $index => $batas) {
            $berikut = self::KELOMPOK_UMUR[$index + 1] ?? null;
            $label[] = $berikut === null ? "{$batas}+" : "{$batas}–".($berikut - 1);
        }

        return $label;
    }

    /**
     * @return list<int> jumlah penduduk per kelompok umur, urut sesuai KELOMPOK_UMUR
     */
    private static function hitungUmur(?int $nagariId, string $jenisKelamin): array
    {
        $query = self::dasar($nagariId)
            ->where('jenis_kelamin', $jenisKelamin)
            ->whereNotNull('tanggal_lahir');

        foreach (self::KELOMPOK_UMUR as $index => $batas) {
            $berikut = self::KELOMPOK_UMUR[$index + 1] ?? null;
            $umur = 'TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE())';

            $query->selectRaw(
                $berikut === null
                    ? "SUM({$umur} >= ?) as kelompok_{$index}"
                    : "SUM({$umur} BETWEEN ? AND ?) as kelompok_{$index}",
                $berikut === null ? [$batas] : [$batas, $berikut - 1],
            );
        }

        $baris = $query->first();

        return array_map(
            fn (int $index): int => (int) ($baris?->{"kelompok_{$index}"} ?? 0),
            array_keys(self::KELOMPOK_UMUR),
        );
    }

    /**
     * Hitung penduduk per baris tabel acuan, terbanyak lebih dulu, plus satu
     * kelompok untuk yang acuannya belum terisi.
     *
     * @return Collection<string, int>
     */
    private static function hitungReferensi(?int $nagariId, string $tabel, string $kolom): Collection
    {
        $hasil = self::dasar($nagariId)
            ->leftJoin($tabel, "{$tabel}.id", '=', "penduduk.{$kolom}")
            ->selectRaw("COALESCE({$tabel}.nama, 'Belum terdata') as label, COUNT(*) as jumlah")
            ->groupBy('label')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'label');

        return $hasil->map(fn ($jumlah): int => (int) $jumlah);
    }

    /**
     * @param  Collection<string, int>  $data
     * @return array<string, mixed>
     */
    private static function batangReferensi(Collection $data, string $warna): array
    {
        if ($data->isEmpty()) {
            $data = collect(['Belum ada data' => 0]);
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 340,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'plotOptions' => [
                'bar' => ['horizontal' => true, 'barHeight' => '70%', 'borderRadius' => 3],
            ],
            'series' => [['name' => 'Penduduk', 'data' => $data->values()->all()]],
            'xaxis' => [
                'categories' => $data->keys()->all(),
                'decimalsInFloat' => 0,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'yaxis' => ['labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']]],
            'colors' => [$warna],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => false],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 4],
        ];
    }

    private static function dasar(?int $nagariId): Builder
    {
        return Penduduk::query()->when($nagariId, fn (Builder $query) => $query->where('penduduk.nagari_id', $nagariId));
    }
}
