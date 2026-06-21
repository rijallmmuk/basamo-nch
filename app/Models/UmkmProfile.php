<?php

namespace App\Models;

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
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = [
        'desa_id', 'user_id', 'umkm_category_id', 'nama_usaha', 'slug',
        'deskripsi', 'alamat', 'whatsapp', 'status',
    ];

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
            ->logOnly(['nama_usaha', 'umkm_category_id', 'whatsapp', 'status', 'desa_id', 'user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('umkm');
    }

    /** Nomor WhatsApp dinormalisasi ke format internasional Indonesia (62xxxx). */
    public function normalizedWhatsapp(): string
    {
        // Buang non-digit lalu semua nol di depan (tangani 0, 00, +62 sekaligus).
        $number = preg_replace('/^0+/', '', preg_replace('/\D/', '', (string) $this->whatsapp));

        return match (true) {
            str_starts_with($number, '62') => $number, // sudah kode negara
            str_starts_with($number, '8') => '62'.$number, // 8xx (eks-0) → 628xx
            default => $number,
        };
    }

    /** URL wa.me dengan nomor dinormalisasi + pesan opsional. */
    public function whatsappUrl(?string $message = null): string
    {
        return 'https://wa.me/'.$this->normalizedWhatsapp().($message ? '?text='.rawurlencode($message) : '');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(UmkmCategory::class, 'umkm_category_id');
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
