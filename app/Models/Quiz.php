<?php

namespace App\Models;

use App\Observers\QuizObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([QuizObserver::class])]
class Quiz extends Model
{
    use LogsActivity;

    protected $fillable = [
        'module_id', 'passing_score', 'max_attempts',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['module_id', 'passing_score', 'max_attempts'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('kuis');
    }

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    /**
     * Label kuis diturunkan dari modulnya (1 modul = 1 kuis, tanpa kolom title).
     */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => 'Kuis: '.($this->module?->title ?? ''));
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
