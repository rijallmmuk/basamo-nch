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

    protected static function booted(): void
    {
        // Hapus permanen modul → bersihkan file PDF tiap halaman lewat model (cascade DB
        // tak memicu event ModulePage::deleted, jadi file bisa yatim). Soft delete tak terdampak.
        static::forceDeleting(function (self $module): void {
            $module->pages()->get()->each->delete();
        });

        // Pindah desa (super admin): Spatie TIDAK meregenerasi slug saat update
        // (doNotGenerateSlugsOnUpdate), jadi slug lama bisa bentrok di desa tujuan.
        // Jamin unik otomatis di ruang baru TANPA membebani user — tambah akhiran
        // (-1, -2, …) hanya bila perlu. Slug tetap stabil bila tak pindah desa.
        static::updating(function (self $module): void {
            if ($module->isDirty('desa_id')) {
                $module->slug = $module->uniqueSlugForDesa($module->slug);
            }
        });
    }

    /**
     * Slug unik di ruang desa modul ini (global = desa_id NULL), memakai slug saat
     * ini sebagai basis + akhiran angka bila bentrok. Index unik (desa_id, slug) ikut
     * menghitung baris terhapus → cek withTrashed agar tak menabrak constraint.
     */
    protected function uniqueSlugForDesa(string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 1;

        while (
            static::withTrashed()
                ->whereKeyNot($this->getKey())
                ->where('desa_id', $this->desa_id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

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
            ->doNotGenerateSlugsOnUpdate()
            // Keunikan slug di-scope PER DESA (global = desa_id NULL) → dua desa boleh
            // punya judul sama. Bentrok dalam ruang yang sama → auto-suffix (-1, -2, …).
            // Spatie sudah memperhitungkan baris ter-arsip, jadi slug modul yang dihapus
            // tak akan dipakai ulang & aman saat modul lama dipulihkan.
            ->extraScope(fn ($query) => $query->where('desa_id', $this->desa_id));
    }

    /**
     * Resolusi route binding URL portal `{module:slug}`. Karena slug unik PER DESA
     * (modul global & modul desa bisa ber-slug sama), batasi ke modul yang terlihat
     * user (global + desanya) dan UTAMAKAN modul desa-sendiri agar URL tak ambigu —
     * modul desa "menutupi" modul global ber-slug sama. Field selain `slug` (mis. `id`
     * untuk panel admin) memakai resolusi default tanpa scope.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        $desaId = auth()->user()?->desa_id;

        return $this->newQuery()
            ->where($field, $value)
            ->where(fn ($query) => $query
                ->whereNull('desa_id')
                ->when($desaId, fn ($q) => $q->orWhere('desa_id', $desaId)))
            ->orderByRaw('desa_id IS NULL') // desa-sendiri (non-null) dulu, lalu global
            ->first();
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
