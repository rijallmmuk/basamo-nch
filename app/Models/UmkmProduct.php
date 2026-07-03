<?php

namespace App\Models;

use App\Enums\UmkmProductStatus;
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
        'umkm_profile_id', 'umkm_category_id', 'nama_produk', 'slug', 'deskripsi', 'harga',
        'status', 'alasan_penolakan', 'approved_by', 'approved_at', 'jumlah_dilihat',
    ];

    protected function casts(): array
    {
        return [
            'status' => UmkmProductStatus::class,
            'harga' => 'integer',
            'approved_at' => 'datetime',
            'jumlah_dilihat' => 'integer',
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
            ->logOnly(['nama_produk', 'umkm_category_id', 'harga', 'status', 'alasan_penolakan', 'umkm_profile_id'])
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(UmkmCategory::class, 'umkm_category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
