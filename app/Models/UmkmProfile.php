<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\TautanPlatform;
use App\Models\Concerns\BelongsToNagari;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class UmkmProfile extends Model implements HasMedia
{
    use BelongsToNagari, HasSlug, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nagari_id', 'user_id', 'nama_usaha', 'slug',
        'deskripsi', 'alamat', 'whatsapp', 'email', 'jam_operasional', 'tahun_berdiri', 'tautan',
        'status', 'jumlah_dilihat',
    ];

    protected static function booted(): void
    {
        // Hapus permanen lapak → hapus produk lewat Eloquent. Cascade DB pada
        // umkm_products.umkm_profile_id melewati event model, sehingga Media Library
        // tak sempat membersihkan foto produk (berkas yatim di storage).
        static::forceDeleting(function (self $profile): void {
            $profile->products()->get()->each->delete();

            // Rekap kunjungan polimorfik tanpa foreign key, jadi tidak ada cascade
            // basis data yang membersihkannya. Hanya pada hapus permanen: lapak
            // yang diarsipkan masih bisa dipulihkan beserta riwayat kunjungannya.
            $profile->views()->delete();
        });

        // Lapak dihapus (arsip MAUPUN permanen) → kapabilitas UMKM pemilik ikut
        // dicabut; dipulihkan → kapabilitas kembali. Tanpa ini warga tanpa lapak
        // tetap pegang akses pengelolaan UMKM yang menggantung. (Event 'deleted'
        // juga berjalan saat force delete.)
        static::deleted(function (self $profile): void {
            $profile->owner?->update(['umkm_access_granted_at' => null]);
        });

        static::restored(function (self $profile): void {
            $profile->owner?->update(['umkm_access_granted_at' => now()]);
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'tahun_berdiri' => 'integer',
            'jumlah_dilihat' => 'integer',
            'tautan' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $disk = config('media-library.disk_name');

        // Logo/foto profil usaha (identitas kartu direktori & etalase) — 1:1.
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useDisk($disk)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        // Sampul/banner pendek halaman etalase usaha — 3:1.
        $this->addMediaCollection('sampul')
            ->singleFile()
            ->useDisk($disk)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        // Gambar QR code usaha (isi QR di luar tanggung jawab platform) — TIDAK dipotong.
        $this->addMediaCollection('qr')
            ->singleFile()
            ->useDisk($disk)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('logo')
            ->fit(Fit::Crop, 400, 400)
            ->format('webp')
            ;

        $this->addMediaConversion('hero')
            ->performOnCollections('sampul')
            ->fit(Fit::Crop, 1800, 600)
            ->format('webp')
            ->quality(82)
            ;

        // QR: perkecil tanpa memotong/mendistorsi (rasio & keterbacaan kode dijaga).
        $this->addMediaConversion('display')
            ->performOnCollections('qr')
            ->fit(Fit::Contain, 600, 600)
            ->format('webp')
            ;
    }

    /** URL logo usaha (konversi thumb) atau gambar default bila belum diunggah. */
    public function logoUrl(): string
    {
        $media = $this->getFirstMedia('logo');

        if (! $media) {
            return asset('images/default-umkm-logo.svg');
        }

        $url = $media->getAvailableUrl(['thumb']);

        return ! empty($url) ? $url : $media->getUrl();
    }

    /** URL sampul/banner etalase (konversi hero) atau gambar default bila belum diunggah. */
    public function sampulUrl(): string
    {
        $media = $this->getFirstMedia('sampul');

        if (! $media) {
            return asset('images/default-umkm-sampul.svg');
        }

        $url = $media->getAvailableUrl(['hero']);

        return ! empty($url) ? $url : $media->getUrl();
    }

    /** URL gambar QR code (konversi display) atau null. */
    public function qrUrl(): ?string
    {
        $media = $this->getFirstMedia('qr');

        return $media?->getAvailableUrl(['display']);
    }

    /**
     * Tautan promosi terstruktur & tervalidasi untuk tampilan publik: hanya entri
     * dengan platform dikenal + URL http(s). Urutan mengikuti input pemilik.
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
            ->generateSlugsFrom('nama_usaha')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_usaha', 'deskripsi', 'whatsapp', 'email', 'jam_operasional', 'tahun_berdiri', 'tautan', 'status', 'nagari_id', 'user_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('umkm');
    }

    /** Nomor WhatsApp dinormalisasi ke format internasional Indonesia (62xxxx). */
    public function normalizedWhatsapp(): string
    {
        return PhoneNumber::normalize($this->whatsapp) ?? '';
    }

    /** URL wa.me dengan nomor dinormalisasi + pesan opsional. */
    public function whatsappUrl(?string $message = null): string
    {
        return 'https://wa.me/'.$this->normalizedWhatsapp().($message ? '?text='.rawurlencode($message) : '');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(UmkmProduct::class);
    }

    /** Rekap kunjungan harian etalase; `jumlah_dilihat` tetap angka seumur hidup. */
    public function views(): MorphMany
    {
        return $this->morphMany(UmkmView::class, 'viewable');
    }
}
