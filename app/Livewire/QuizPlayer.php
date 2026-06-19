<?php

namespace App\Livewire;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Notifications\QuizCompleted;
use App\Services\LmsPointService;
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
                $this->quizErrors[] = "Soal #{$question->sort_order} belum dijawab.";
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

        $scoreSum = 0.0;
        $totalQuestions = 0;

        foreach ($this->questions as $question) {
            $totalQuestions++;

            // Soal bisa punya >1 jawaban benar → nilai partial credit per soal.
            $selectedIds = collect((array) ($this->answers[$question->id] ?? []))
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->unique();

            $correctIds = $question->options->where('is_correct', true)->pluck('id');
            $incorrectIds = $question->options->where('is_correct', false)->pluck('id');

            $correctSelected = $selectedIds->intersect($correctIds)->count();
            $wrongSelected = $selectedIds->intersect($incorrectIds)->count();

            // frac = max(0, (benar terpilih / total benar) − (salah terpilih / total salah))
            $penalty = $incorrectIds->count() > 0 ? $wrongSelected / $incorrectIds->count() : 0;
            $fraction = max(0, ($correctSelected / max($correctIds->count(), 1)) - $penalty);

            $scoreSum += $fraction;

            // Simpan satu baris per opsi yang dipilih warga.
            foreach ($selectedIds as $optionId) {
                QuizAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_id' => $optionId,
                    'is_correct' => $correctIds->contains($optionId),
                ]);
            }
        }

        // Nilai dinormalisasi 0–100 dari total fraksi soal benar.
        $percentage = $totalQuestions > 0 ? (int) round(($scoreSum / $totalQuestions) * 100) : 0;
        $passed = $percentage >= $this->quiz->passing_score;

        $attempt->update([
            'score' => $percentage,
            'status' => $passed ? 'passed' : 'failed',
        ]);

        $this->resultStatus = $passed ? 'passed' : 'failed';
        $this->resultScore = $percentage;
        $this->submitted = true;

        if ($passed) {
            app(LmsPointService::class)->awardQuizPass(auth()->user(), $this->quiz);

            $this->dispatch('confetti');
            $this->dispatch('toast', type: 'xp', title: '+'.LmsPointService::QUIZ_XP.' XP', message: 'Selamat, kamu lulus kuis!');
        }

        auth()->user()->notify(new QuizCompleted($this->quiz, $percentage, $passed));
    }

    public function render()
    {
        return view('livewire.quiz-player');
    }
}
