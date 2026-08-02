<?php

namespace App\Models;

use App\Observers\EvaluasiPertanyaanObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([EvaluasiPertanyaanObserver::class])]
class EvaluasiPertanyaan extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluasi_id', 'pertanyaan', 'urutan',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Auto-urut: soal baru ditaruh di urutan terakhir evaluasinya.
        static::creating(function (EvaluasiPertanyaan $pertanyaan): void {
            if (empty($pertanyaan->urutan)) {
                $pertanyaan->urutan = (static::where('evaluasi_id', $pertanyaan->evaluasi_id)->max('urutan') ?? 0) + 1;
            }
        });
    }

    public function evaluasi(): BelongsTo
    {
        return $this->belongsTo(Evaluasi::class);
    }

    public function opsis(): HasMany
    {
        return $this->hasMany(EvaluasiOpsi::class, 'pertanyaan_id')->orderBy('urutan')->orderBy('id');
    }
}
