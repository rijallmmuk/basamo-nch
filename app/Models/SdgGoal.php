<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Poin (Tujuan) SDGs Desa 1..18. Referensi global — di-seed dari Permendesa 13/2025.
 * Poin 18 = "Kelembagaan Nagari Dinamis dan Budaya Nagari Adaptif".
 */
class SdgGoal extends Model
{
    protected $fillable = ['sdg_pillar_id', 'nomor', 'nama', 'slug', 'deskripsi', 'warna', 'ikon'];

    protected function casts(): array
    {
        return ['nomor' => 'integer'];
    }

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(SdgPillar::class, 'sdg_pillar_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SdgTarget::class)->orderBy('id');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(SdgAchievement::class);
    }

    /** URL ikon resmi SDGs (webp) di public/img/sdgs. */
    public function ikonUrl(): string
    {
        return asset($this->ikon ?: "img/sdgs/{$this->nomor}.webp");
    }
}
