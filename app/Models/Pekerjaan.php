<?php

namespace App\Models;

use App\Models\Concerns\IsLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Pekerjaan extends Model
{
    use IsLookup;

    protected $table = 'pekerjaan';

    protected $fillable = ['kode', 'nama', 'urutan', 'aktif'];

    protected static function booted(): void
    {
        // `kode` (nomor baku Dukcapil 01–99) tak ditampilkan di form Data Master —
        // entri baru diberi nomor lanjutan otomatis (kolom NOT NULL + unik).
        static::creating(function (self $pekerjaan): void {
            if (blank($pekerjaan->kode)) {
                $maks = (int) static::query()->max(DB::raw('CAST(kode AS UNSIGNED)'));
                $pekerjaan->kode = str_pad((string) ($maks + 1), 2, '0', STR_PAD_LEFT);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function penduduk(): HasMany
    {
        return $this->hasMany(Penduduk::class);
    }
}
