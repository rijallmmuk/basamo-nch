<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pilar SDGs Desa (Sosial, Lingkungan, Ekonomi, Hukum & Tata Kelola).
 * Referensi global — di-seed dari Permendesa 13/2025.
 */
class SdgPillar extends Model
{
    protected $fillable = ['nama', 'slug', 'warna'];

    public function goals(): HasMany
    {
        return $this->hasMany(SdgGoal::class)->orderBy('nomor');
    }
}
