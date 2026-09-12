<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Nagari extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama', 'slug', 'wilayah_kode', 'provinsi', 'kabupaten', 'kecamatan',
        'koordinat_lat', 'koordinat_lng', 'status',
    ];

    protected static function booted(): void
    {
        // Slug publik digenerate sekali saat dibuat & STABIL (alamat yang dibagikan
        // nagari tak boleh berubah saat nama diedit). Nama nagari tak unik nasional →
        // bentrok diberi akhiran -kabupaten, lalu -2, -3, … (termasuk baris trashed,
        // karena unique komposit deleted_at tak menahan duplikat aktif di MariaDB).
        static::creating(function (Nagari $nagari): void {
            if (blank($nagari->slug)) {
                $nagari->slug = static::slugUnik($nagari);
            }
        });

        // Jika admin secara sadar mengubah kode wilayah (ganti nagari),
        // slug wajib di-regenerate agar tidak nyangkut ke nagari lama.
        static::updating(function (Nagari $nagari): void {
            if ($nagari->isDirty('wilayah_kode')) {
                $nagari->slug = static::slugUnik($nagari);
            }
        });
    }

    /**
     * Slug SATU KATA tanpa simbol apa pun (bukan kebab-case) — dipakai langsung
     * sebagai subdomain publik (`{slug}.basamonch.com`), yang harus polos tanpa
     * tanda hubung. Disambiguasi bentrok juga tanpa simbol: gabung nama kabupaten,
     * lalu tempel angka urut.
     */
    /**
     * Nagari pemilik situs berdasarkan host permintaan, atau null bila host itu
     * domain induk / bukan subdomain nagari.
     *
     * Dibutuhkan karena `/login`, `/portal`, dan `/panel` sengaja TIDAK terikat
     * domain (agar dapat dibuka dari subdomain mana pun), sehingga route-nya tak
     * punya parameter `{nagari}` yang bisa dibaca. Konteks nagarinya harus
     * diturunkan sendiri dari host.
     */
    public static function fromHost(?string $host): ?self
    {
        $host = mb_strtolower(trim((string) $host));
        $base = mb_strtolower((string) config('app.public_base_domain'));

        if ($host === '' || $base === '' || $base === 'localhost') {
            return null;
        }

        // Buang porta agar `nagari.basamonch.com:8000` saat pengembangan tetap
        // dikenali sama seperti di produksi.
        $host = explode(':', $host)[0];
        $akhiran = '.'.$base;

        if (! str_ends_with($host, $akhiran)) {
            return null;
        }

        $slug = mb_substr($host, 0, -mb_strlen($akhiran));

        // Slug bertitik berarti subdomain bertingkat (a.b.basamonch.com), bukan
        // situs nagari. Juga jaga agar `www` tak pernah diperlakukan sebagai slug.
        if ($slug === '' || str_contains($slug, '.') || static::subdomainTerlarang($slug)) {
            return null;
        }

        return static::query()->where('slug', $slug)->first();
    }

    /** Slug bentrok dengan subdomain layanan (lihat config/subdomain.php). */
    public static function subdomainTerlarang(?string $slug): bool
    {
        return in_array(mb_strtolower((string) $slug), config('subdomain.reserved', []), true);
    }

    private static function slugUnik(Nagari $nagari): string
    {
        $dasar = str($nagari->nama)->slug('')->limit(140, '')->toString() ?: 'nagari';

        // Selain bentrok antar nagari, slug juga tak boleh menabrak subdomain
        // layanan (www, mail, cpanel, …). Nagari yang memakainya bertabrakan di
        // tingkat DNS dan situsnya tak akan pernah bisa dibuka.
        $slugSudahDipakai = fn (string $slug): bool => static::subdomainTerlarang($slug)
            || static::withTrashed()
                ->where('slug', $slug)
                ->when($nagari->exists, fn ($query) => $query->whereKeyNot($nagari->getKey()))
                ->exists();

        $kandidat = collect([$dasar, $nagari->kabupaten ? $dasar.str($nagari->kabupaten)->slug('') : null])
            ->filter()
            ->first(fn (string $slug): bool => ! $slugSudahDipakai($slug));

        if ($kandidat !== null) {
            return $kandidat;
        }

        for ($i = 2; ; $i++) {
            if (! $slugSudahDipakai($dasar.$i)) {
                return $dasar.$i;
            }
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'koordinat_lat' => 'decimal:8',
            'koordinat_lng' => 'decimal:8',
        ];
    }

    /** Nama lengkap dengan penyebutan administratif, mis. "Nagari Koto Tuo". */
    public function getNamaLengkapAttribute(): string
    {
        return 'Nagari '.$this->nama;
    }

    /** Sebutan administratif nagari ini — statis, selalu "Nagari". */
    public function sebutan(): string
    {
        return 'Nagari';
    }

    /**
     * Username login operator nagari = digit kode nagari (tanpa simbol),
     * mis. "13.06.01.2001" → "1306012001". Analog warga login pakai NIK.
     */
    public static function usernameFromKode(?string $kode): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $kode);

        return $digits !== '' ? $digits : null;
    }

    /** Username operator nagari ini, diturunkan dari kode nagari (null bila kode kosong). */
    public function defaultOperatorUsername(): ?string
    {
        return static::usernameFromKode($this->wilayah_kode);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'wilayah_kode', 'status', 'kabupaten', 'kecamatan'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('nagari');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('sampul')
            ->useDisk(config('media-library.disk_name'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('hero')
            ->performOnCollections('sampul')
            ->fit(Fit::Crop, 1920, 1080)
            ->format('webp')
            ->quality(82)
            ;
    }

    /**
     * Foto sampul hero beranda situs nagari, terurut (bisa lebih dari satu → slider).
     * Kosong bila belum diunggah (view pakai foto generik).
     *
     * @return Collection<int, string>
     */
    public function sampulUrls(): Collection
    {
        return $this->getMedia('sampul')->map(fn (Media $media): string => $media->getAvailableUrl(['hero']))->values();
    }

    /** Logo kab/kota induk, dari referensi wilayah (via kode wilayah nagari). */
    public function kabupatenLogoUrl(): ?string
    {
        if (! $this->wilayah_kode) {
            return null;
        }

        $parts = explode('.', $this->wilayah_kode);

        if (count($parts) < 2) {
            return null;
        }

        return RefWilayah::find($parts[0].'.'.$parts[1])?->logoUrl();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Produk UMKM milik nagari (lewat profil usaha). Juga dipakai Laravel untuk
     * scoped route binding subdomain {nagari:slug}/produk/{product:slug} —
     * produk nagari lain otomatis 404 di level binding.
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(UmkmProduct::class, UmkmProfile::class);
    }

    /** Identitas warga nagari; setiap record wajib memiliki satu akun User. */
    public function warga(): HasMany
    {
        return $this->hasMany(Penduduk::class);
    }

    /** Akun operator utama nagari (satu per nagari, dikelola dari form Nagari). */
    public function operator(): HasOne
    {
        return $this->hasOne(User::class)->role('operator');
    }

    /**
     * Pelaksanaan pelatihan yang MENYASAR nagari ini (pivot `pelatihan_nagari`).
     * Modul nagari diturunkan dari sini, lihat {@see Module::scopeForNagari()}.
     */
    public function pelatihans(): BelongsToMany
    {
        return $this->belongsToMany(Pelatihan::class, 'pelatihan_nagari')
            ->withTimestamps();
    }

    public function umkmProfiles(): HasMany
    {
        return $this->hasMany(UmkmProfile::class);
    }

    public function ewsDevices(): HasMany
    {
        return $this->hasMany(EwsDevice::class);
    }

    public function penduduk(): HasMany
    {
        return $this->warga();
    }

    public function sdgAchievements(): HasMany
    {
        return $this->hasMany(SdgAchievement::class);
    }

    public function idmStatuses(): HasMany
    {
        return $this->hasMany(IdmStatus::class);
    }

    /** Status IDM tahun TERBARU (untuk tampilan; di-cache Eloquent setelah akses pertama). */
    public function latestIdmStatus(): HasOne
    {
        return $this->hasOne(IdmStatus::class)->ofMany('tahun', 'max');
    }

    /** Pelaksanaan yang DISELENGGARAKAN nagari ini (kolom `pelatihans.nagari_id`). */
    public function pelatihanDiselenggarakan(): HasMany
    {
        return $this->hasMany(Pelatihan::class);
    }

    /** Berita yang diterbitkan oleh nagari ini. */
    public function beritas(): HasMany
    {
        return $this->hasMany(Berita::class);
    }
}
