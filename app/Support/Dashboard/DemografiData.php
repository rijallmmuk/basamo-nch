<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Models\Penduduk;
use App\Models\User;
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
     * Angka dasar kependudukan yang dipakai bersama oleh dashboard dan halaman
     * publik. Penduduk dan akun portal sengaja tetap menjadi dua populasi berbeda.
     *
     * @return array{penduduk:int, akun_portal:int, laki_laki:int, perempuan:int, belum_terdata:int}
     */
    public static function ringkasan(?int $nagariId = null, bool $hanyaNagariAktif = false): array
    {
        $baris = self::dasar($nagariId, $hanyaNagariAktif)
            ->selectRaw('COUNT(*) AS penduduk')
            ->selectRaw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) AS laki_laki")
            ->selectRaw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) AS perempuan")
            ->selectRaw("SUM(CASE WHEN jenis_kelamin IS NULL OR jenis_kelamin NOT IN ('L', 'P') THEN 1 ELSE 0 END) AS belum_terdata")
            ->first();

        $akun = User::query()
            ->role('warga')
            // "Akun portal" adalah kapasitas warga yang benar-benar dapat login,
            // bukan jumlah seluruh baris akun yang pernah dibuat. Akun nonaktif
            // tetap dapat dilihat pada resource pengguna, tetapi tidak boleh
            // menggelembungkan indikator adopsi platform.
            ->where('users.status', ActiveStatus::Active)
            ->when($nagariId, fn (Builder $query) => $query->where('users.nagari_id', $nagariId))
            ->when($hanyaNagariAktif && $nagariId === null, fn (Builder $query) => $query
                ->whereHas('nagari', fn (Builder $nagari) => $nagari->where('status', ActiveStatus::Active)))
            ->count();

        return [
            'penduduk' => (int) ($baris?->penduduk ?? 0),
            'akun_portal' => $akun,
            'laki_laki' => (int) ($baris?->laki_laki ?? 0),
            'perempuan' => (int) ($baris?->perempuan ?? 0),
            'belum_terdata' => (int) ($baris?->belum_terdata ?? 0),
        ];
    }

    /** @return Collection<string, int> */
    public static function kelompokUmur(?int $nagariId = null, bool $hanyaNagariAktif = false): Collection
    {
        return collect(self::labelUmur())
            ->combine(self::hitungUmur($nagariId, null, $hanyaNagariAktif));
    }

    /** @return Collection<string, int> */
    public static function distribusiPendidikan(?int $nagariId = null, bool $hanyaNagariAktif = false): Collection
    {
        return self::hitungReferensi($nagariId, 'pendidikan', 'pendidikan_id', $hanyaNagariAktif);
    }

    /** @return Collection<string, int> */
    public static function distribusiPekerjaan(?int $nagariId = null, bool $hanyaNagariAktif = false): Collection
    {
        return self::hitungReferensi($nagariId, 'pekerjaan', 'pekerjaan_id', $hanyaNagariAktif)->take(10);
    }

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
    public static function tanpaTanggalLahir(?int $nagariId = null, bool $hanyaNagariAktif = false): int
    {
        return self::dasar($nagariId, $hanyaNagariAktif)->whereNull('tanggal_lahir')->count();
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
    private static function hitungUmur(?int $nagariId, ?string $jenisKelamin, bool $hanyaNagariAktif = false): array
    {
        $query = self::dasar($nagariId, $hanyaNagariAktif)
            ->when($jenisKelamin !== null, fn (Builder $query) => $query->where('jenis_kelamin', $jenisKelamin))
            ->whereNotNull('tanggal_lahir');

        foreach (self::KELOMPOK_UMUR as $index => $batas) {
            $berikut = self::KELOMPOK_UMUR[$index + 1] ?? null;
            $lahirMaksimal = now()->startOfDay()->subYears($batas)->toDateString();

            if ($berikut === null) {
                $query->selectRaw(
                    "SUM(CASE WHEN tanggal_lahir <= ? THEN 1 ELSE 0 END) as kelompok_{$index}",
                    [$lahirMaksimal],
                );

                continue;
            }

            $lahirMinimalEksklusif = now()->startOfDay()->subYears($berikut)->toDateString();
            $query->selectRaw(
                "SUM(CASE WHEN tanggal_lahir <= ? AND tanggal_lahir > ? THEN 1 ELSE 0 END) as kelompok_{$index}",
                [$lahirMaksimal, $lahirMinimalEksklusif],
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
    private static function hitungReferensi(
        ?int $nagariId,
        string $tabel,
        string $kolom,
        bool $hanyaNagariAktif = false,
    ): Collection
    {
        $hasil = self::dasar($nagariId, $hanyaNagariAktif)
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

    private static function dasar(?int $nagariId, bool $hanyaNagariAktif = false): Builder
    {
        return Penduduk::query()
            ->when($nagariId, fn (Builder $query) => $query->where('penduduk.nagari_id', $nagariId))
            ->when($hanyaNagariAktif && $nagariId === null, fn (Builder $query) => $query
                ->whereHas('nagari', fn (Builder $nagari) => $nagari->where('status', ActiveStatus::Active)));
    }
}
