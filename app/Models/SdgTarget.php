<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sasaran SDGs Desa (mis. '1.1'). Referensi global. Punya banyak indikator (1:N).
 * `sub_tema` hanya untuk Poin 18: 'kelembagaan' | 'budaya'.
 */
class SdgTarget extends Model
{
    protected $fillable = ['sdg_goal_id', 'kode', 'deskripsi', 'sub_tema'];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(SdgGoal::class, 'sdg_goal_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(SdgIndicator::class)->orderBy('id');
    }
}
