<?php

namespace App\Livewire;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Notifications\QuizCompleted;
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
        // Validasi semua soal sudah dijawab
        $this->quizErrors = [];

        foreach ($this->questions as $question) {
            if (empty($this->answers[$question->id])) {
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

        $correctCount = 0;
        $totalQuestions = 0;

        foreach ($this->questions as $question) {
            $totalQuestions++;
            $value = $this->answers[$question->id] ?? null;

            $selectedOption = $question->options->firstWhere('id', (int) $value);
            $isCorrect = $selectedOption?->is_correct ?? false;

            QuizAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_id' => $selectedOption?->id,
                'is_correct' => $isCorrect,
            ]);

            if ($isCorrect) {
                $correctCount++;
            }
        }

        // Nilai dinormalisasi 0–100 berdasarkan jumlah jawaban benar.
        $percentage = $totalQuestions > 0 ? (int) round(($correctCount / $totalQuestions) * 100) : 0;
        $passed = $percentage >= $this->quiz->passing_score;

        $attempt->update([
            'score' => $percentage,
            'status' => $passed ? 'passed' : 'failed',
        ]);

        $this->resultStatus = $passed ? 'passed' : 'failed';
        $this->resultScore = $percentage;
        $this->submitted = true;

        auth()->user()->notify(new QuizCompleted($this->quiz, $percentage, $passed));
    }

    public function render()
    {
        return view('livewire.quiz-player');
    }
}
