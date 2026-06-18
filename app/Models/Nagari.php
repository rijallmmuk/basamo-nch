<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nagari extends Model
{
    use HasFactory, SoftDeletes;

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
