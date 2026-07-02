<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class UmkmCategory extends Model
{
    use HasSlug;

    protected $fillable = ['nama', 'slug', 'icon', 'urutan'];

    protected static function booted(): void
    {
        // Auto-urut: kategori baru ditaruh di urutan terakhir (ubah urutan = seret di tabel).
        static::creating(function (self $category): void {
            if (empty($category->urutan)) {
                $category->urutan = (static::max('urutan') ?? 0) + 1;
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('nama')
            ->saveSlugsTo('slug');
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return static::orderBy('urutan')->pluck('nama', 'id')->all();
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(UmkmProfile::class);
    }
}
