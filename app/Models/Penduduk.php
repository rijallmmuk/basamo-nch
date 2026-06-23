<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Models\Concerns\BelongsToDesa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Identitas kependudukan (lapisan 1 dari arsitektur 3 lapisan): siapa orangnya,
 * terlepas dari apakah punya akun. Akun login = `User` (tertaut via `users.penduduk_id`).
 */
class Penduduk extends Model
{
    use BelongsToDesa, LogsActivity, SoftDeletes;

    protected $table = 'penduduk';

    protected $fillable = [
        'nik', 'nama', 'desa_id', 'desa_unit_id', 'tempat_lahir', 'tanggal_lahir',
        'jenis_kelamin', 'agama_id', 'status_perkawinan_id', 'pekerjaan_id', 'jabatan_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nik', 'nama', 'desa_id', 'desa_unit_id', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama_id', 'status_perkawinan_id', 'pekerjaan_id', 'jabatan_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('penduduk');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'tanggal_lahir' => 'date',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function desaUnit(): BelongsTo
    {
        return $this->belongsTo(DesaUnit::class);
    }

    public function agama(): BelongsTo
    {
        return $this->belongsTo(Agama::class);
    }

    public function statusPerkawinan(): BelongsTo
    {
        return $this->belongsTo(StatusPerkawinan::class);
    }

    public function pekerjaan(): BelongsTo
    {
        return $this->belongsTo(Pekerjaan::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
