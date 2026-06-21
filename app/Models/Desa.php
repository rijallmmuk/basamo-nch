<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Desa extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama', 'jenis_desa_id', 'kode', 'provinsi', 'kabupaten', 'kecamatan', 'jenis_sub_unit_id',
        'koordinat_lat', 'koordinat_lng', 'kontak', 'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    /** Nama lengkap dengan penyebutan administratif, mis. "Nagari Koto Tuo". */
    public function getNamaLengkapAttribute(): string
    {
        $jenis = $this->jenisDesa?->nama;

        return trim(($jenis ? $jenis.' ' : '').$this->nama);
    }

    /** Sebutan sub-unit desa ini; fallback umum bila belum diatur admin desa. */
    public function subUnitLabel(): string
    {
        return $this->jenisSubUnit?->nama ?: 'Sub-Unit Wilayah';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'jenis_desa_id', 'kode', 'status', 'kabupaten', 'kecamatan', 'jenis_sub_unit_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('desa');
    }

    /** Logo desa (opsional) + logo kabupaten/kota induk. */
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

    public function jenisDesa(): BelongsTo
    {
        return $this->belongsTo(JenisDesa::class);
    }

    public function jenisSubUnit(): BelongsTo
    {
        return $this->belongsTo(JenisSubUnit::class);
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
