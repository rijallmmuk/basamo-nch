<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDesa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpLog extends Model
{
    use BelongsToDesa;

    protected $fillable = [
        'user_id', 'desa_id', 'sumber', 'sumber_id', 'jumlah',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sumber_id' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
