<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModulePage extends Model
{
    protected $fillable = [
        'module_id', 'title', 'type', 'content',
        'video_url', 'file_path', 'order',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
