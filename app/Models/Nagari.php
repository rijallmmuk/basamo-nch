<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Nagari extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama', 'kode', 'provinsi', 'kabupaten', 'kecamatan', 'wilayah_label',
        'koordinat_lat', 'koordinat_lng', 'kontak', 'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'kode', 'status', 'kabupaten', 'kecamatan', 'wilayah_label'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('nagari');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function wilayah(): HasMany
    {
        return $this->hasMany(Wilayah::class);
    }

    public function umkmProfiles(): HasMany
    {
        return $this->hasMany(UmkmProfile::class);
    }
}
