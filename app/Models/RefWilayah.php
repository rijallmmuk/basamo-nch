<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referensi wilayah administratif resmi (Kepmendagri). Tabel datar; hierarki
 * lewat `parent_kode`. Metadata geo (lat/lng/luas/penduduk) hanya prov & kab/kota;
 * geometri batas peta ada di tabel terpisah `wilayah_boundaries` (join via `kode`).
 */
class RefWilayah extends Model
{
    public const LEVEL_PROVINSI = 1;

    public const LEVEL_KABUPATEN = 2;

    public const LEVEL_KECAMATAN = 3;

    public const LEVEL_DESA = 4;

    protected $table = 'ref_wilayah';

    protected $primaryKey = 'kode';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'kode', 'nama', 'level', 'parent_kode', 'ibukota',
        'lat', 'lng', 'elv', 'tz', 'luas', 'penduduk',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'elv' => 'float',
            'tz' => 'integer',
            'luas' => 'float',
            'penduduk' => 'integer',
        ];
    }

    /** @param  Builder<RefWilayah>  $query */
    public function scopeLevel(Builder $query, int $level): void
    {
        $query->where('level', $level);
    }

    /** Anak langsung dari sebuah kode (untuk dropdown bertingkat). */
    public function scopeChildrenOf(Builder $query, ?string $parentKode): void
    {
        $query->where('parent_kode', $parentKode);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_kode', 'kode');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_kode', 'kode');
    }

    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class, 'wilayah_kode', 'kode');
    }

    /** Logo kab/kota (level 2) by-convention; null bila berkasnya tak ada. */
    public function logoUrl(): ?string
    {
        if ($this->level !== self::LEVEL_KABUPATEN) {
            return null;
        }

        $relative = 'images/wilayah/'.$this->kode.'.png';

        return file_exists(public_path($relative)) ? asset($relative) : null;
    }
}
