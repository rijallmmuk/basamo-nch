<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSubUnit extends Model
{
    protected $table = 'jenis_sub_unit';

    protected $fillable = ['nama', 'urutan', 'aktif'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class);
    }
}
