<?php

namespace App\Support\Dashboard;

use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\UmkmView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal angka kunjungan berbasis waktu untuk dasbor UMKM.
 *
 * Semua widget pemilik lapak membaca dari sini supaya "30 hari terakhir" berarti
 * rentang yang persis sama di kartu ringkasan, grafik, dan tabel produk.
 *
 * Catatan penting yang mengikat seluruh tampilan: rekap harian baru dimulai sejak
 * fitur ini ada, sedangkan `jumlah_dilihat` sudah berjalan sejak awal. Karena itu
 * angka rentang waktu TIDAK BOLEH disajikan seolah bagian dari total seumur hidup,
 * dan tidak boleh dipakai menghitung selisih "sisanya".
 */
class UmkmKunjunganData
{
    public const HARI = 30;

    /** Awal rentang default, dipakai bersama agar semua widget sejajar. */
    public static function sejak(int $hari = self::HARI): Carbon
    {
        return now()->startOfDay()->subDays($hari - 1);
    }

    /**
     * Deret kunjungan harian lapak dan produknya, siap dipakai grafik garis.
     *
     * @return array{tanggal: list<string>, lapak: list<int>, produk: list<int>}
     */
    public static function tren(UmkmProfile $profile, int $hari = self::HARI): array
    {
        $dari = self::sejak($hari);
        $sampai = now()->endOfDay();

        $lapak = self::harian(UmkmProfile::class, [$profile->getKey()], $dari, $sampai);
        $produk = self::harian(UmkmProduct::class, self::produkIds($profile), $dari, $sampai);

        $tanggal = [];
        $deretLapak = [];
        $deretProduk = [];

        // Hari tanpa kunjungan tetap harus muncul sebagai nol; tanpa ini grafik
        // memampatkan hari sepi dan tren jadi terbaca lebih ramai dari kenyataan.
        for ($tgl = $dari->copy(); $tgl->lte($sampai); $tgl->addDay()) {
            $kunci = $tgl->toDateString();
            $tanggal[] = $tgl->translatedFormat('d M');
            $deretLapak[] = $lapak[$kunci] ?? 0;
            $deretProduk[] = $produk[$kunci] ?? 0;
        }

        return ['tanggal' => $tanggal, 'lapak' => $deretLapak, 'produk' => $deretProduk];
    }

    /** Total kunjungan etalase lapak pada rentang. */
    public static function kunjunganLapak(UmkmProfile $profile, int $hari = self::HARI): int
    {
        return (int) UmkmView::query()
            ->untuk(UmkmProfile::class, [$profile->getKey()])
            ->rentang(self::sejak($hari), now())
            ->sum('jumlah');
    }

    /** Total kunjungan seluruh produk lapak pada rentang. */
    public static function kunjunganProduk(UmkmProfile $profile, int $hari = self::HARI): int
    {
        $ids = self::produkIds($profile);

        return $ids === []
            ? 0
            : (int) UmkmView::query()
                ->untuk(UmkmProduct::class, $ids)
                ->rentang(self::sejak($hari), now())
                ->sum('jumlah');
    }

    /**
     * Kunjungan per produk pada rentang, dikunci id produk. Dipakai menambahkan
     * kolom "30 hari" di samping angka seumur hidup pada tabel dan grafik produk.
     *
     * @param  iterable<int>  $produkIds
     * @return Collection<int, int>
     */
    public static function perProduk(iterable $produkIds, int $hari = self::HARI): Collection
    {
        $ids = collect($produkIds)->all();

        if ($ids === []) {
            return collect();
        }

        return UmkmView::query()
            ->untuk(UmkmProduct::class, $ids)
            ->rentang(self::sejak($hari), now())
            ->selectRaw('viewable_id, SUM(jumlah) as total')
            ->groupBy('viewable_id')
            ->pluck('total', 'viewable_id')
            ->map(fn ($total) => (int) $total);
    }

    /**
     * Apakah rekap harian sudah punya isi. Dipakai widget untuk memilih antara
     * menampilkan tren atau memberi tahu bahwa pencatatan baru berjalan, alih-alih
     * menyajikan grafik datar yang terbaca sebagai "tidak ada pengunjung".
     */
    public static function adaRekap(UmkmProfile $profile): bool
    {
        return UmkmView::query()
            ->untuk(UmkmProfile::class, [$profile->getKey()])
            ->exists()
            || UmkmView::query()
                ->untuk(UmkmProduct::class, self::produkIds($profile))
                ->exists();
    }

    /** @return list<int> */
    private static function produkIds(UmkmProfile $profile): array
    {
        return $profile->products()->pluck('umkm_products.id')->all();
    }

    /**
     * @param  iterable<int>  $ids
     * @return Collection<string, int>
     */
    private static function harian(string $tipe, iterable $ids, Carbon $dari, Carbon $sampai): Collection
    {
        $daftar = collect($ids)->all();

        if ($daftar === []) {
            return collect();
        }

        return UmkmView::query()
            ->untuk($tipe, $daftar)
            ->rentang($dari, $sampai)
            ->selectRaw('tanggal, SUM(jumlah) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal')
            ->mapWithKeys(fn ($total, $tanggal) => [
                Carbon::parse($tanggal)->toDateString() => (int) $total,
            ]);
    }
}
