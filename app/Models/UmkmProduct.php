<?php

namespace App\Models;

use App\Enums\TautanPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Produk UMKM. TANPA status terbit: begitu dibuat, produk langsung tampil di
 * etalase selama lapaknya aktif. TANPA soft delete pula, sehingga menghapus
 * produk benar-benar menghapusnya beserta seluruh fotonya (Media Library ikut
 * membersihkan berkas karena penghapusannya permanen).
 */
class UmkmProduct extends Model implements HasMedia
{
    use HasSlug, InteractsWithMedia, LogsActivity;

    protected static function booted(): void
    {
        // Produk dihapus permanen (tidak punya arsip), jadi rekap kunjungannya ikut
        // dibuang. Relasi polimorfik tak punya foreign key yang mencascade sendiri.
        static::deleted(fn (self $product) => $product->views()->delete());
    }

    protected $fillable = [
        'umkm_profile_id', 'umkm_category_id', 'nama_produk', 'slug', 'deskripsi', 'tautan', 'harga',
        'jumlah_dilihat',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'jumlah_dilihat' => 'integer',
            'tautan' => 'array',
        ];
    }

    /**
     * Tautan promosi produk tervalidasi untuk tampilan publik (platform dikenal +
     * URL http(s)); urutan mengikuti input pemilik.
     *
     * @return Collection<int, array{platform: TautanPlatform, url: string}>
     */
    public function tautanLinks(): Collection
    {
        return Collection::make($this->tautan ?? [])
            ->map(function ($item): ?array {
                $platform = TautanPlatform::tryFrom((string) ($item['platform'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                return $platform !== null && str_starts_with($url, 'http')
                    ? ['platform' => $platform, 'url' => $url]
                    : null;
            })
            ->filter()
            ->values();
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
            ->logOnly(['nama_produk', 'umkm_category_id', 'harga', 'tautan', 'umkm_profile_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('produk');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->useDisk(config('media-library.disk_name'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 600, 600)
            ->format('webp')
            ;

        // Foto utama halaman detail (galeri) — rasio produk DITETAPKAN 1:1, sama seperti kartu.
        $this->addMediaConversion('detail')
            ->fit(Fit::Crop, 1200, 1200)
            ->format('webp')
            ;
    }

    /** URL foto pertama (konversi card) atau placeholder. */
    public function coverUrl(): string
    {
        $media = $this->getFirstMedia('photos');

        if (! $media) {
            return asset('images/default-product.svg');
        }

        $url = $media->getAvailableUrl(['card']);

        return ! empty($url) ? $url : $media->getUrl();
    }

    /**
     * URL foto beresolusi besar untuk galeri detail.
     *
     * Produk lama mungkin hanya memiliki konversi `card`. Meminta `detail`
     * dengan getUrl() tetap menghasilkan alamat URL walaupun berkas hasil
     * konversinya belum pernah dibuat. getAvailableUrl() mencegah gambar rusak
     * dengan memilih detail, lalu card, dan terakhir berkas asli.
     */
    public function detailPhotoUrl(Media $media): string
    {
        return $media->getAvailableUrl(['detail', 'card']);
    }

    /** URL thumbnail galeri dengan fallback aman ke berkas asli. */
    public function thumbnailPhotoUrl(Media $media): string
    {
        return $media->getAvailableUrl(['card']);
    }

    public function umkmProfile(): BelongsTo
    {
        return $this->belongsTo(UmkmProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(UmkmCategory::class, 'umkm_category_id');
    }

    /** Rekap kunjungan harian; `jumlah_dilihat` tetap memegang angka seumur hidup. */
    public function views(): MorphMany
    {
        return $this->morphMany(UmkmView::class, 'viewable');
    }
}
