<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Wilayah extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = ['nagari_id', 'nama'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'nagari_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('wilayah');
    }

    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
