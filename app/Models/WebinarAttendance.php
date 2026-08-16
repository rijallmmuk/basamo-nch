<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Warga menandai dirinya mengikuti pertemuan daring sebuah pelatihan.
 *
 * Webinar tidak punya modul maupun materi, jadi tidak ada progres yang dapat menjadi
 * bukti mengikuti. Baris inilah penggantinya, sekaligus satu-satunya syarat terbitnya
 * sertifikat webinar.
 */
class WebinarAttendance extends Model
{
    protected $fillable = ['pelatihan_id', 'user_id', 'hadir_pada'];

    protected function casts(): array
    {
        return ['hadir_pada' => 'datetime'];
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
