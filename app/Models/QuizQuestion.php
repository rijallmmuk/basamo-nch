<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question', 'order',
    ];

    protected static function booted(): void
    {
        // Auto-urut: soal baru ditaruh di urutan terakhir kuisnya.
        static::creating(function (QuizQuestion $question) {
            if (empty($question->order)) {
                $question->order = (static::where('quiz_id', $question->quiz_id)->max('order') ?? 0) + 1;
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class, 'question_id')->orderBy('order')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'question_id');
    }
}
