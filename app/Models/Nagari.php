<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Nagari extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama', 'jenis', 'kode', 'provinsi', 'kabupaten', 'kecamatan', 'wilayah_label',
        'koordinat_lat', 'koordinat_lng', 'kontak', 'status',
    ];

    /**
     * Penyebutan wilayah administratif setingkat desa di Indonesia (skala nasional).
     * String + dropdown agar fleksibel; daftar bisa ditambah tanpa migrasi.
     *
     * @var list<string>
     */
    public const JENIS = [
        'Desa', 'Kelurahan', 'Nagari', 'Gampong', 'Kampung', 'Kalurahan',
        'Lembang', 'Pekon', 'Tiyuh', 'Negeri', 'Nagori', 'Huta',
    ];

    /**
     * Penyebutan sub-unit di bawah desa (dusun/lingkungan dan padanan daerahnya).
     *
     * @var list<string>
     */
    public const SUB_UNIT = [
        'Dusun', 'Lingkungan', 'Jorong', 'Korong', 'Dukuh', 'Padukuhan',
        'Banjar', 'Kampung', 'Lorong', 'RW',
    ];

    /** @return array<string, string> */
    public static function jenisOptions(): array
    {
        return array_combine(self::JENIS, self::JENIS);
    }

    /** @return array<string, string> */
    public static function subUnitOptions(): array
    {
        return array_combine(self::SUB_UNIT, self::SUB_UNIT);
    }

    /** Nama lengkap dengan penyebutan administratif, mis. "Nagari Koto Tuo". */
    public function getNamaLengkapAttribute(): string
    {
        return trim(($this->jenis ? $this->jenis.' ' : '').$this->nama);
    }

    /** Sebutan sub-unit nagari ini; fallback umum bila belum diatur admin nagari. */
    public function subUnitLabel(): string
    {
        return $this->wilayah_label ?: 'Sub-Unit Wilayah';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'jenis', 'kode', 'status', 'kabupaten', 'kecamatan', 'wilayah_label'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('nagari');
    }

    /** Logo nagari (opsional) + logo kabupaten/kota induk. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
        $this->addMediaCollection('logo_kabupaten')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }

    public function logoUrl(): ?string
    {
        $media = $this->getFirstMedia('logo');

        return $media ? $media->getUrl() : null;
    }

    public function kabupatenLogoUrl(): ?string
    {
        $media = $this->getFirstMedia('logo_kabupaten');

        return $media ? $media->getUrl() : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function wilayah(): HasMany
    {
        return $this->hasMany(Wilayah::class);
    }

    public function umkmProfiles(): HasMany
    {
        return $this->hasMany(UmkmProfile::class);
    }
}
