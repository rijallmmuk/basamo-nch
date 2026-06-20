<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Discussion extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'module_id', 'user_id', 'parent_id', 'body', 'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    /** Audit moderasi: sematan + hapus/pulihkan (event delete/restore otomatis dicatat). */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['is_pinned'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('diskusi');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Discussion::class, 'parent_id');
    }
}
