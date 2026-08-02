<?php

namespace App\Models;

use App\Observers\EvaluasiOpsiObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([EvaluasiOpsiObserver::class])]
class EvaluasiOpsi extends Model
{
    use HasFactory;

    protected $fillable = [
        'pertanyaan_id', 'teks_opsi', 'is_correct', 'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(EvaluasiPertanyaan::class, 'pertanyaan_id');
    }
}
