<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDesa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Sub-unit di dalam desa (Jorong/Dusun/Korong/…), 1 tingkat. Tabel `desa_units`.
 * Sebutannya diatur per desa via `desas.jenis_sub_unit_id`.
 */
class DesaUnit extends Model
{
    use BelongsToDesa, LogsActivity, SoftDeletes;

    protected $fillable = ['desa_id', 'nama'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'desa_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('wilayah');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Warga (akun penduduk) yang beralamat di sub-unit ini — tak termasuk admin. */
    public function warga(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'warga');
    }
}
