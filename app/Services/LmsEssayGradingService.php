<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\User;

class LmsEssayGradingService
{
    public function finalize(QuizAttempt $attempt, User $reviewer): QuizAttempt
    {
        $attempt->load(['answers.question', 'quiz.questions']);

        $totalScore = 0;
        $maxScore = 0;

        foreach ($attempt->quiz->questions as $question) {
            $maxScore += $question->points;

            $answer = $attempt->answers->firstWhere('question_id', $question->id);

            if (! $answer) {
                continue;
            }

            if ($question->type === 'multiple_choice') {
                if ($answer->is_correct) {
                    $totalScore += $question->points;
                }
            } else {
                $totalScore += (int) ($answer->score_given ?? 0);
            }
        }

        $percentage = $maxScore > 0 ? (int) round(($totalScore / $maxScore) * 100) : 0;

        $attempt->update([
            'score' => $percentage,
            'status' => $percentage >= $attempt->quiz->passing_score ? 'passed' : 'failed',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $attempt->fresh();
    }

    public function hasUngradedEssays(QuizAttempt $attempt): bool
    {
        return $attempt->answers()
            ->whereHas('question', fn ($q) => $q->where('type', 'essay'))
            ->whereNull('score_given')
            ->exists();
    }
}
