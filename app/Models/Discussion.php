<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Discussion extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'module_id', 'user_id', 'parent_id', 'isi', 'is_pinned',
    ];

    protected static function booted(): void
    {
        static::saving(function (Discussion $discussion): void {
            if ($discussion->module_id === null) {
                throw new \LogicException('Diskusi harus terikat pada satu modul.');
            }

            if ($discussion->parent_id === null) {
                return;
            }

            $parent = Discussion::query()->find($discussion->parent_id);

            if (! $parent
                || $parent->parent_id !== null
                || $parent->module_id !== $discussion->module_id) {
                throw new \LogicException('Balasan diskusi harus berada pada thread dan modul yang sama.');
            }
        });
    }

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
            ->dontLogEmptyChanges()
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

    public function reads(): HasMany
    {
        return $this->hasMany(DiscussionRead::class);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsReadBy(User $user): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function markAsUnreadBy(User $user): void
    {
        $this->reads()->where('user_id', $user->id)->delete();
    }

    public function scopeReadBy($query, User $user)
    {
        return $query->whereHas('reads', fn ($q) => $q->where('user_id', $user->id));
    }
}
