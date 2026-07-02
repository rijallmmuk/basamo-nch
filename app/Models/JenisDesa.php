<?php

namespace App\Models;

use App\Models\Concerns\IsLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisDesa extends Model
{
    use IsLookup;

    protected $table = 'jenis_desa';

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
