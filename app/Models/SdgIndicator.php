<?php

namespace App\Models;

use App\Enums\MetodeNilaiIndikator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Indikator SDGs Desa (mis. '1.1.1') — alat ukur sebuah sasaran. Referensi global.
 * Di mode "rinci", indikator menjadi field input (via sdg_indicator_values).
 * `metode` menentukan cara nilai mentah diubah jadi skor 0–100.
 */
class SdgIndicator extends Model
{
    protected $fillable = ['sdg_target_id', 'kode', 'deskripsi', 'metode', 'target_nilai', 'satuan_acuan'];

    protected function casts(): array
    {
        return [
            'metode' => MetodeNilaiIndikator::class,
            'target_nilai' => 'decimal:2',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SdgTarget::class, 'sdg_target_id');
    }
}
