<?php

namespace App\Models;

use App\Enums\ActiveStatus;
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
        'desa_id', 'user_id', 'umkm_category_id', 'nama_usaha', 'slug',
        'deskripsi', 'alamat', 'whatsapp', 'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
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
            ->logOnly(['nama_usaha', 'umkm_category_id', 'whatsapp', 'status', 'desa_id', 'user_id'])
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
