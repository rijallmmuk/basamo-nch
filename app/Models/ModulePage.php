<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ModulePage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'module_id', 'title', 'type', 'content',
        'video_url', 'file_path', 'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'type', 'module_id', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('materi');
    }

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
