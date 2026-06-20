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
        'nagari_id', 'user_id', 'umkm_category_id', 'nama_usaha', 'slug',
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
            ->logOnly(['nama_usaha', 'umkm_category_id', 'whatsapp', 'status', 'nagari_id', 'user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('umkm');
    }

    /** URL wa.me dengan nomor dinormalisasi (08xx → 628xx) + pesan opsional. */
    public function whatsappUrl(?string $message = null): string
    {
        $number = preg_replace('/\D/', '', (string) $this->whatsapp);
        $number = preg_replace('/^0/', '62', $number);

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class);
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
