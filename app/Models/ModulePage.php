<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModulePage extends Model
{
    protected $fillable = [
        'module_id', 'title', 'type', 'content',
        'video_url', 'file_path', 'sort_order',
    ];

    protected static function booted(): void
    {
        // Auto-urut: halaman baru ditaruh di urutan terakhir modulnya.
        static::creating(function (ModulePage $page) {
            if (empty($page->sort_order)) {
                $page->sort_order = (static::where('module_id', $page->module_id)->max('sort_order') ?? 0) + 1;
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
