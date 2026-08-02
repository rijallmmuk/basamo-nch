<?php

namespace App\Models;

use App\Enums\DimensiIdm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu indikator IDM (skor 0-5) di bawah sebuah {@see IdmStatus} (nagari×tahun),
 * berkelompok per dimensi (IKS/IKE/IKL). Menyimpan kondisi (keterangan), kegiatan yang
 * dapat dilakukan bila lemah, `nilai` = "+NILAI" (penambahan indeks bila kegiatan itu
 * dilakukan), dan `pelaksana` = "yang dapat melaksanakan kegiatan" ({level: instansi}).
 */
class IdmIndicator extends Model
{
    protected $fillable = [
        'idm_status_id', 'dimensi', 'nomor', 'indikator', 'skor', 'keterangan', 'kegiatan', 'nilai', 'pelaksana',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dimensi' => DimensiIdm::class,
            'nomor' => 'integer',
            'skor' => 'integer',
            'nilai' => 'decimal:6',
            'pelaksana' => 'array',
        ];
    }

    public function idmStatus(): BelongsTo
    {
        return $this->belongsTo(IdmStatus::class);
    }
}
