<?php

namespace App\Models;

use App\Models\Concerns\BelongsToNagari;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capaian sebuah Poin SDGs untuk satu nagari (potret berjalan, tanpa dimensi
 * tahun): persentase ditarik dari API Kemendesa (lihat SdgKemendesaService) via
 * job terjadwal. SEMUA 18 poin dihitung — tak ada konsep relevansi (mengikuti
 * praktik nyata penilaian SDGs Desa).
 */
class SdgAchievement extends Model
{
    use BelongsToNagari;

    protected $fillable = [
        'nagari_id', 'sdg_goal_id', 'persentase', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'persentase' => 'decimal:2',
            'fetched_at' => 'datetime',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(SdgGoal::class, 'sdg_goal_id');
    }
}
