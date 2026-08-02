<?php

namespace App\Models;

use App\Models\Concerns\IsLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pendidikan extends Model
{
    use IsLookup;

    protected $table = 'pendidikan';

    protected $fillable = ['nama', 'aktif'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function penduduk(): HasMany
    {
        return $this->hasMany(Penduduk::class);
    }
}
