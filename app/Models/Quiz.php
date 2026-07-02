<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quiz extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'module_id', 'nilai_lulus', 'maks_percobaan',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['module_id', 'nilai_lulus', 'maks_percobaan'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('kuis');
    }

    protected function casts(): array
    {
        return [
            'nilai_lulus' => 'integer',
            'maks_percobaan' => 'integer',
        ];
    }

    /**
     * Label kuis diturunkan dari modulnya (1 modul = 1 kuis, tanpa kolom title).
     */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => 'Kuis: '.($this->module?->judul ?? ''));
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('urutan')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
