<?php

namespace App\Models;

use App\Enums\KategoriBerita;
use App\Enums\StatusBerita;
use App\Models\Concerns\BelongsToNagari;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Berita extends Model implements HasMedia
{
    use BelongsToNagari, HasFactory, HasSlug, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'judul',
        'slug',
        'ringkasan',
        'konten',
        'kategori',
        'status',
        'is_pinned',
        'published_at',
        'penulis_nama',
        'created_by',
        'nagari_id',
        'semua_nagari',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriBerita::class,
            'status' => StatusBerita::class,
            'is_pinned' => 'boolean',
            'semua_nagari' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('judul')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'kategori', 'status', 'is_pinned', 'semua_nagari', 'nagari_id', 'published_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('berita');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('sampul')
            ->useDisk(config('media-library.disk_name'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('lampiran')
            ->useDisk(config('media-library.disk_name'))
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 800, 500)
            ->format('webp');

        $this->addMediaConversion('hero')
            ->fit(Fit::Crop, 1200, 630)
            ->format('webp');
    }

    public function sampulUrl(?string $conversion = 'card'): ?string
    {
        $media = $this->getFirstMedia('sampul');
        if (! $media) {
            return null;
        }

        if ($conversion) {
            $url = $media->getAvailableUrl([$conversion]);
            if (! empty($url)) {
                return $url;
            }
        }

        return $media->getUrl();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class, 'nagari_id');
    }

    public function nagaris(): BelongsToMany
    {
        return $this->belongsToMany(Nagari::class, 'berita_nagari')->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', StatusBerita::Diterbitkan)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeForNagari(Builder $query, Nagari|int $nagari): Builder
    {
        $nagariId = $nagari instanceof Nagari ? $nagari->id : $nagari;

        return $query->where(function (Builder $q) use ($nagariId) {
            $q->where('semua_nagari', true)
                ->orWhere('nagari_id', $nagariId)
                ->orWhereHas('nagaris', fn (Builder $sub) => $sub->where('nagaris.id', $nagariId));
        });
    }

    public function targetAudienceLabel(): string
    {
        if ($this->semua_nagari) {
            return 'Seluruh Nagari';
        }

        $this->loadMissing(['nagari', 'nagaris']);

        if ($this->nagaris->isNotEmpty()) {
            if ($this->nagaris->count() === 1) {
                return 'Nagari ' . $this->nagaris->first()->nama;
            }
            return $this->nagaris->count() . ' Nagari Terpilih';
        }

        if ($this->nagari) {
            return 'Nagari ' . $this->nagari->nama;
        }

        return 'Umum';
    }

    public function authorLabel(): string
    {
        if (filled($this->penulis_nama)) {
            return $this->penulis_nama;
        }

        $this->loadMissing(['creator', 'nagari']);

        if ($this->nagari) {
            return 'Pemerintah Nagari ' . $this->nagari->nama;
        }

        if ($this->creator) {
            return $this->creator->name;
        }

        return 'Humas BASAMO NCH';
    }

    public function readingTime(): int
    {
        $words = str_word_count(strip_tags((string) $this->konten));

        return max(1, (int) ceil($words / 200));
    }
}
