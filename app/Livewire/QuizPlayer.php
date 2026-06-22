<?php

namespace App\Livewire;

use App\Enums\QuizAttemptStatus;
use App\Models\Module;
use App\Models\Quiz;
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

    /**
     * Apakah user masih boleh mengerjakan kuis ini (sumber kebenaran server)?
     * Diblokir bila sudah lulus atau batas percobaan habis.
     */
    private function canAttempt(): bool
    {
        $userId = auth()->id();

        $alreadyPassed = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->quiz->id)
            ->where('status', QuizAttemptStatus::Passed)
            ->exists();

        if ($alreadyPassed) {
            return false;
        }

        if ($this->quiz->maks_percobaan > 0) {
            $finishedAttempts = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $this->quiz->id)
                ->whereIn('status', [QuizAttemptStatus::Passed, QuizAttemptStatus::Failed])
                ->count();

            if ($finishedAttempts >= $this->quiz->maks_percobaan) {
                return false;
            }
        }

        return true;
    }

    public function submit(): void
    {
        // Sudah dikumpulkan di sesi komponen ini — cegah submit ganda.
        if ($this->submitted) {
            return;
        }

        // Defensif: kuis tanpa soal tak bisa dikumpulkan (normalnya sudah diblokir controller).
        if ($this->questions->isEmpty()) {
            return;
        }

        // Re-validasi kelayakan di server. Gating di controller hanya berlaku saat GET;
        // tanpa ini, submit() bisa dipanggil berulang via Livewire untuk melewati
        // batas percobaan atau mengulang setelah sudah lulus.
        if (! $this->canAttempt()) {
            $this->quizErrors = ['Kamu tidak dapat mengerjakan kuis ini lagi.'];

            return;
        }

        // Validasi semua soal sudah dijawab
        $this->quizErrors = [];

        foreach ($this->questions as $question) {
            if (empty($this->answers[$question->id])) {
                $this->quizErrors[] = "Soal #{$question->urutan} belum dijawab.";
            }
        }

        if (! empty($this->quizErrors)) {
            return;
        }

        $scoreSum = 0.0;
        $totalQuestions = 0;
        $answerRows = [];

        foreach ($this->questions as $question) {
            $totalQuestions++;

            $correctIds = $question->options->where('is_correct', true)->pluck('id');
            $incorrectIds = $question->options->where('is_correct', false)->pluck('id');

            // Soal bisa punya >1 jawaban benar → nilai partial credit per soal.
            // Saring ke opsi milik soal ini saja: cegah ID asing dari klien memicu
            // error FK saat simpan atau mengotori data.
            $selectedIds = collect((array) ($this->answers[$question->id] ?? []))
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->unique()
                ->intersect($question->options->pluck('id'))
                ->values();

            $correctSelected = $selectedIds->intersect($correctIds)->count();
            $wrongSelected = $selectedIds->intersect($incorrectIds)->count();

            // frac = max(0, (benar terpilih / total benar) − (salah terpilih / total salah))
            $penalty = $incorrectIds->count() > 0 ? $wrongSelected / $incorrectIds->count() : 0;
            $fraction = max(0, ($correctSelected / max($correctIds->count(), 1)) - $penalty);

            $scoreSum += $fraction;

            // Satu baris per opsi yang dipilih warga (disimpan setelah attempt dibuat).
            foreach ($selectedIds as $optionId) {
                $answerRows[] = [
                    'question_id' => $question->id,
                    'selected_option_id' => $optionId,
                    'is_correct' => $correctIds->contains($optionId),
                ];
            }
        }

        // Nilai dinormalisasi 0–100 dari total fraksi soal benar.
        $percentage = $totalQuestions > 0 ? (int) round(($scoreSum / $totalQuestions) * 100) : 0;
        $passed = $percentage >= $this->quiz->nilai_lulus;

        // Auto-grade sinkron: simpan attempt langsung berstatus final (tanpa state transien).
        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'quiz_id' => $this->quiz->id,
            'nilai' => $percentage,
            'status' => $passed ? QuizAttemptStatus::Passed : QuizAttemptStatus::Failed,
            'submitted_at' => now(),
        ]);

        $attempt->answers()->createMany($answerRows);

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
