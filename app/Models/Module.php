<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Module extends Model
{
    use SoftDeletes, HasSlug;

    protected $fillable = [
        'nagari_id', 'title', 'slug', 'description', 'thumbnail',
        'order', 'prerequisite_module_id', 'status', 'created_by',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'prerequisite_module_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ModulePage::class)->orderBy('order');
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
