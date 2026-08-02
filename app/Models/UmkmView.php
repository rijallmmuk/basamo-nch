<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rekap kunjungan harian etalase usaha dan detail produk.
 *
 * Baris ditulis lewat {@see self::catat()} saja, dari jalur yang sudah lolos
 * deduplikasi pengunjung di controller publik, supaya jumlah harian di sini
 * selalu sejalan dengan kenaikan `jumlah_dilihat`.
 */
class UmkmView extends Model
{
    protected $fillable = ['viewable_type', 'viewable_id', 'tanggal', 'jumlah'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Naikkan rekap hari ini untuk satu entitas.
     *
     * Ditulis sebagai upsert satu perintah, bukan baca-lalu-simpan: dua pengunjung
     * pada detik yang sama tidak boleh membuat baris kembar, dan penjaganya adalah
     * indeks unik harian, bukan pemeriksaan di PHP.
     */
    public static function catat(Model $viewable, ?Carbon $waktu = null): void
    {
        $tanggal = ($waktu ?? now())->toDateString();
        $sekarang = now();

        static::query()->upsert(
            [[
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id' => $viewable->getKey(),
                'tanggal' => $tanggal,
                'jumlah' => 1,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]],
            ['viewable_type', 'viewable_id', 'tanggal'],
            // Sengaja bukan `jumlah` biasa: nilai lama harus DITAMBAH, bukan ditimpa.
            ['jumlah' => DB::raw('jumlah + 1'), 'updated_at' => $sekarang],
        );
    }

    /** Batasi ke entitas tertentu tanpa perlu memuat modelnya. */
    public function scopeUntuk(Builder $query, string $tipe, iterable $ids): Builder
    {
        return $query->where('viewable_type', $tipe)->whereIn('viewable_id', $ids);
    }

    /** Rentang tanggal inklusif, dipakai seluruh widget analitik UMKM. */
    public function scopeRentang(Builder $query, Carbon $dari, Carbon $sampai): Builder
    {
        return $query->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()]);
    }
}
