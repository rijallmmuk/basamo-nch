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
        'nama', 'kode', 'provinsi', 'kabupaten', 'kecamatan', 'wilayah_label',
        'koordinat_lat', 'koordinat_lng', 'kontak', 'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'kode', 'status', 'kabupaten', 'kecamatan', 'wilayah_label'])
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
