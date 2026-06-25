<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Desa extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama', 'jenis_desa_id', 'wilayah_kode', 'provinsi', 'kabupaten', 'kecamatan', 'jenis_sub_unit_id',
        'koordinat_lat', 'koordinat_lng', 'kontak', 'status',
    ];

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
        $jenis = $this->jenisDesa?->nama;

        return trim(($jenis ? $jenis.' ' : '').$this->nama);
    }

    /** Sebutan sub-unit desa ini; fallback umum "Wilayah" bila belum diatur admin desa. */
    public function subUnitLabel(): string
    {
        return $this->jenisSubUnit?->nama ?: 'Wilayah';
    }

    /**
     * Username login admin desa = digit kode nagari (tanpa simbol),
     * mis. "13.06.01.2001" → "1306012001". Analog warga login pakai NIK.
     */
    public static function usernameFromKode(?string $kode): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $kode);

        return $digits !== '' ? $digits : null;
    }

    /** Username admin desa ini, diturunkan dari kode nagari (null bila kode kosong). */
    public function defaultAdminUsername(): ?string
    {
        return static::usernameFromKode($this->wilayah_kode);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'jenis_desa_id', 'wilayah_kode', 'status', 'kabupaten', 'kecamatan', 'jenis_sub_unit_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('desa');
    }

    /** Logo desa (opsional). Logo kabupaten/kota diturunkan dari data wilayah. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }

    public function logoUrl(): ?string
    {
        $media = $this->getFirstMedia('logo');

        return $media ? $media->getUrl() : null;
    }

    /** Logo kab/kota induk, dari referensi wilayah (via kode wilayah desa). */
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

    public function jenisDesa(): BelongsTo
    {
        return $this->belongsTo(JenisDesa::class);
    }

    public function jenisSubUnit(): BelongsTo
    {
        return $this->belongsTo(JenisSubUnit::class);
    }

    public function refWilayah(): BelongsTo
    {
        return $this->belongsTo(RefWilayah::class, 'wilayah_kode', 'kode');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Warga desa (penduduk; tak termasuk admin). UMKM owner = warga + flag akses. */
    public function warga(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'warga');
    }

    /** Akun admin utama desa (satu per desa, dikelola dari form Desa). */
    public function desaAdmin(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'desa_admin');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function desaUnits(): HasMany
    {
        return $this->hasMany(DesaUnit::class);
    }

    public function umkmProfiles(): HasMany
    {
        return $this->hasMany(UmkmProfile::class);
    }
}
