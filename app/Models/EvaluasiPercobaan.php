<?php

namespace App\Models;

use App\Enums\StatusPercobaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluasiPercobaan extends Model
{
    protected $fillable = [
        'user_id', 'evaluasi_id', 'nilai', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPercobaan::class,
            'nilai' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluasi(): BelongsTo
    {
        return $this->belongsTo(Evaluasi::class);
    }
}
