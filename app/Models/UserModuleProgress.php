<?php

namespace App\Models;

use App\Enums\ModuleProgressStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModuleProgress extends Model
{
    protected $fillable = [
        'user_id', 'module_id', 'halaman_selesai',
        'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModuleProgressStatus::class,
            'halaman_selesai' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
