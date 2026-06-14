<?php

namespace App\Livewire;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QuizPlayer extends Component
{
    public Quiz $quiz;

    public Module $module;

    public array $answers = [];

    public bool $submitted = false;

    public string $resultStatus = '';

    public ?int $resultScore = null;

    public array $quizErrors = [];

    public function mount(Quiz $quiz): void
    {
        $this->quiz = $quiz;
        $this->module = $quiz->module;
    }

    #[Computed]
    public function questions(): Collection
    {
        return $this->quiz->questions()->with('options')->get();
    }

    public function submit(): void
    {
        // Validasi semua soal pilihan ganda sudah dijawab
        $this->quizErrors = [];

        foreach ($this->questions as $question) {
            if ($question->type === 'multiple_choice' && empty($this->answers[$question->id])) {
                $this->quizErrors[] = "Soal #{$question->order} belum dijawab.";
            }
        }

        if (! empty($this->quizErrors)) {
            return;
        }

        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'submitted_at' => now(),
        ]);

        $hasEssay = false;
        $totalScore = 0;
        $maxScore = 0;

        foreach ($this->questions as $question) {
            $maxScore += $question->points;
            $value = $this->answers[$question->id] ?? null;

            if ($question->type === 'multiple_choice') {
                $selectedOption = $question->options->firstWhere('id', (int) $value);
                $isCorrect = $selectedOption?->is_correct ?? false;

                QuizAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOption?->id,
                    'is_correct' => $isCorrect,
                    'score_given' => $isCorrect ? $question->points : 0,
                ]);

                if ($isCorrect) {
                    $totalScore += $question->points;
                }
            } else {
                $hasEssay = true;

                QuizAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'answer_text' => $value,
                    'is_correct' => null,
                    'score_given' => null,
                ]);
            }
        }

        if ($hasEssay) {
            $attempt->update(['status' => 'pending_review']);
            $this->resultStatus = 'pending_review';
        } else {
            $percentage = $maxScore > 0 ? (int) round(($totalScore / $maxScore) * 100) : 0;
            $passed = $percentage >= $this->quiz->passing_score;

            $attempt->update([
                'score' => $percentage,
                'status' => $passed ? 'passed' : 'failed',
                'submitted_at' => now(),
            ]);

            $this->resultStatus = $passed ? 'passed' : 'failed';
            $this->resultScore = $percentage;
        }

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.quiz-player');
    }
}
