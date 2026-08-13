<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sertifikat yang sudah terbit atas nama seorang warga untuk satu pelatihan.
 *
 * Isinya tidak pernah diperbarui. Nomor seri dan tanggal terbit adalah janji kepada
 * pihak luar yang memverifikasi, jadi keduanya harus tetap sama sepanjang umur baris.
 */
class Certificate extends Model
{
    protected $fillable = ['pelatihan_id', 'user_id', 'nomor_seri', 'diterbitkan_pada'];

    protected function casts(): array
    {
        return ['diterbitkan_pada' => 'datetime'];
    }

    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
