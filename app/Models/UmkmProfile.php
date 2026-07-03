<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\PengajuanUmkmStatus;
use App\Models\Concerns\BelongsToDesa;
use App\Support\PhoneNumber;
use Database\Factories\UmkmProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class UmkmProfile extends Model
{
    /** @use HasFactory<UmkmProfileFactory> */
    use BelongsToDesa, HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = [
        'desa_id', 'user_id', 'nama_usaha', 'slug',
        'alamat', 'whatsapp', 'status',
        'status_pengajuan', 'alasan_penolakan_pengajuan', 'diajukan_at',
    ];

    protected static function booted(): void
    {
        // Hapus permanen lapak → hapus produk lewat Eloquent. Cascade DB pada
        // umkm_products.umkm_profile_id melewati event model, sehingga Media Library
        // tak sempat membersihkan foto produk (berkas yatim di storage).
        static::forceDeleting(function (self $profile): void {
            $profile->products()->withTrashed()->get()->each->forceDelete();
        });

        // Lapak dihapus (arsip MAUPUN permanen) → kapabilitas UMKM pemilik ikut
        // dicabut; dipulihkan → kapabilitas kembali. Tanpa ini warga tanpa lapak
        // tetap pegang akses "Produk Saya" yang menggantung. (Event 'deleted'
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
            'status_pengajuan' => PengajuanUmkmStatus::class,
            'diajukan_at' => 'datetime',
        ];
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
            ->logOnly(['nama_usaha', 'whatsapp', 'status', 'desa_id', 'user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
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
}
