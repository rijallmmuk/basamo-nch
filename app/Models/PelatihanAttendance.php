<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Warga menandai dirinya mengikuti pertemuan daring sebuah pelatihan.
 *
 * Pelatihan yang diisi pemateri lewat pertemuan daring boleh tidak berisi modul sama
 * sekali, sehingga tidak ada progres yang dapat menjadi bukti mengikutinya. Baris
 * inilah penggantinya, sekaligus satu-satunya syarat terbitnya sertifikat pelatihan
 * semacam itu.
 */
class PelatihanAttendance extends Model
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
