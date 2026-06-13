<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nagari extends Model
{
    protected $fillable = [
        'nama', 'kode', 'provinsi', 'kabupaten', 'kecamatan',
        'koordinat_lat', 'koordinat_lng', 'kontak', 'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
}
