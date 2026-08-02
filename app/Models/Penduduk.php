<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Models\Concerns\BelongsToNagari;
use App\Models\Concerns\RedactsSensitiveActivityProperties;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Identitas kependudukan (lapisan 1 dari arsitektur 3 lapisan): siapa orangnya,
 * terlepas dari apakah punya akun. Akun login = `User` (tertaut via `users.penduduk_id`).
 */
class Penduduk extends Model
{
    use BelongsToNagari, LogsActivity, RedactsSensitiveActivityProperties, SoftDeletes;

    protected $table = 'penduduk';

    protected $fillable = [
        'nik', 'nama', 'nagari_id', 'tempat_lahir', 'tanggal_lahir',
        'jenis_kelamin', 'agama_id', 'pendidikan_id', 'status_perkawinan_id', 'pekerjaan_id',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $penduduk): void {
            $user = $penduduk->user;

            if ($user === null) {
                return;
            }

            $penduduk->isForceDeleting()
                ? $user->forceDelete()
                : $user->delete();
        });

        static::restored(function (self $penduduk): void {
            $penduduk->user?->restore();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nik', 'nama', 'nagari_id', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama_id', 'pendidikan_id', 'status_perkawinan_id', 'pekerjaan_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('penduduk');
    }

    protected function sensitiveActivityAttributes(): array
    {
        return ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'tanggal_lahir' => 'date',
        ];
    }

    /** Satu-satunya akun login milik warga ini. */
    public function user(): HasOne
    {
        return $this->hasOne(User::class)->withTrashed();
    }

    public function agama(): BelongsTo
    {
        return $this->belongsTo(Agama::class);
    }

    public function pendidikan(): BelongsTo
    {
        return $this->belongsTo(Pendidikan::class);
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
