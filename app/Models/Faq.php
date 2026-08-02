<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FAQ beranda publik (base URL, bukan per-nagari) — dikelola superadmin,
 * ditampilkan di section #faq resources/views/public/home.blade.php.
 */
class Faq extends Model
{
    protected $fillable = ['pertanyaan', 'jawaban', 'urutan', 'aktif'];

    protected static function booted(): void
    {
        // Auto-urut: FAQ baru ditaruh di urutan terakhir (ubah urutan = seret di tabel).
        static::creating(function (self $faq): void {
            if (empty($faq->urutan)) {
                $faq->urutan = (static::max('urutan') ?? 0) + 1;
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}
