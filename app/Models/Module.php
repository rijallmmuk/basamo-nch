<?php

namespace App\Models;

use App\Enums\ModuleStatus;
use App\Models\Concerns\BelongsToDesa;
use App\Observers\ModuleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy([ModuleObserver::class])]
class Module extends Model implements HasMedia
{
    use BelongsToDesa, HasSlug, InteractsWithMedia, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'status', 'desa_id', 'urutan', 'estimasi_menit', 'prasyarat_module_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('modul');
    }

    protected $fillable = [
        'desa_id', 'judul', 'slug', 'deskripsi',
        'urutan', 'estimasi_menit', 'prasyarat_module_id', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModuleStatus::class,
            'urutan' => 'integer',
            'estimasi_menit' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Optimasi berat halaman: crop 16:9 + format webp. nonQueued = langsung jadi
        // tanpa perlu queue worker (cocok MVP).
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 800, 450)
            ->format('webp')
            ->nonQueued();
    }

    /**
     * URL cover (konversi 'card') dengan fallback ke cover default global.
     */
    public function coverUrl(): string
    {
        $media = $this->getFirstMedia('cover');

        return $media
            ? $media->getUrl('card')
            : asset('images/default-module-cover.svg');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('judul')
            ->saveSlugsTo('slug')
            // Slug stabil: tidak berubah saat judul diedit (URL/bookmark tetap valid).
            ->doNotGenerateSlugsOnUpdate();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'prasyarat_module_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ModulePage::class)->orderBy('urutan')->orderBy('id');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }
}
