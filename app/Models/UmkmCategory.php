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
