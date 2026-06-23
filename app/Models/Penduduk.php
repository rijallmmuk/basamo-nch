<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Models\Concerns\BelongsToDesa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'jenis_kelamin', 'agama_id', 'status_perkawinan_id', 'pekerjaan_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nik', 'nama', 'desa_id', 'desa_unit_id', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama_id', 'status_perkawinan_id', 'pekerjaan_id'])
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

    /** Akun(-akun) login milik orang ini. 1 orang boleh punya >1 akun (1 akun = 1 role). */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
}
