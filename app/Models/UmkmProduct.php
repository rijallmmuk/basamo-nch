<?php

namespace App\Models;

use Database\Factories\UmkmProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class UmkmProduct extends Model implements HasMedia
{
    /** @use HasFactory<UmkmProductFactory> */
    use HasFactory, HasSlug, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'umkm_profile_id', 'nama_produk', 'slug', 'deskripsi', 'harga',
        'status', 'rejection_reason', 'approved_by', 'approved_at', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'approved_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('nama_produk')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_produk', 'harga', 'status', 'rejection_reason', 'umkm_profile_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('produk');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 600, 600)
            ->format('webp')
            ->nonQueued();
    }

    /** URL foto pertama (konversi card) atau placeholder. */
    public function coverUrl(): string
    {
        $media = $this->getFirstMedia('photos');

        return $media
            ? $media->getUrl('card')
            : asset('images/default-product.svg');
    }

    public function umkmProfile(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
