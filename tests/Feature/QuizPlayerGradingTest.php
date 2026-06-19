<?php

use App\Livewire\QuizPlayer;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** Buat satu kuis 1-soal dengan opsi terdefinisi. */
function makeSingleQuestionQuiz(array $options, int $passingScore = 50): QuizQuestion
{
    $module = Module::create([
        'title' => 'Modul Uji '.uniqid(),
        'slug' => 'modul-uji-'.uniqid(),
        'status' => 'published',
        'sort_order' => 1,
    ]);

    $quiz = Quiz::create([
        'module_id' => $module->id,
        'passing_score' => $passingScore,
        'max_attempts' => 3,
    ]);

    $question = $quiz->questions()->create(['question' => 'Soal uji?']);

    foreach ($options as $i => $opt) {
        $question->options()->create([
            'option_text' => $opt['text'],
            'is_correct' => $opt['correct'],
            'sort_order' => $i + 1,
        ]);
    }

    return $question->load('options', 'quiz');
}

beforeEach(function () {
    $nagari = Nagari::factory()->create();
    $this->warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);
    $this->actingAs($this->warga);
});

it('soal pilihan tunggal: jawaban benar bernilai 100 dan lulus', function () {
    $q = makeSingleQuestionQuiz([
        ['text' => 'Benar', 'correct' => true],
        ['text' => 'Salah', 'correct' => false],
    ]);
    $correctId = $q->options->firstWhere('is_correct', true)->id;

    Livewire::test(QuizPlayer::class, ['quiz' => $q->quiz])
        ->set('answers', [$q->id => $correctId])
        ->call('submit');

    $attempt = QuizAttempt::first();
    expect($attempt->score)->toBe(100)
        ->and($attempt->status)->toBe('passed');
});

it('soal pilihan tunggal: jawaban salah bernilai 0 dan gagal', function () {
    $q = makeSingleQuestionQuiz([
        ['text' => 'Benar', 'correct' => true],
        ['text' => 'Salah', 'correct' => false],
    ]);
    $wrongId = $q->options->firstWhere('is_correct', false)->id;

    Livewire::test(QuizPlayer::class, ['quiz' => $q->quiz])
        ->set('answers', [$q->id => $wrongId])
        ->call('submit');

    $attempt = QuizAttempt::first();
    expect($attempt->score)->toBe(0)
        ->and($attempt->status)->toBe('failed');
});

it('soal pilihan jamak: memilih semua jawaban benar bernilai 100', function () {
    $q = makeSingleQuestionQuiz([
        ['text' => 'Benar A', 'correct' => true],
        ['text' => 'Benar B', 'correct' => true],
        ['text' => 'Salah', 'correct' => false],
    ]);
    $correctIds = $q->options->where('is_correct', true)->pluck('id')->all();

    Livewire::test(QuizPlayer::class, ['quiz' => $q->quiz])
        ->set('answers', [$q->id => $correctIds])
        ->call('submit');

    expect(QuizAttempt::first()->score)->toBe(100);
});

it('soal pilihan jamak: memilih sebagian benar mendapat partial credit', function () {
    // 2 benar, pilih 1 → fraksi 0.5 → nilai 50.
    $q = makeSingleQuestionQuiz([
        ['text' => 'Benar A', 'correct' => true],
        ['text' => 'Benar B', 'correct' => true],
        ['text' => 'Salah', 'correct' => false],
    ]);
    $oneCorrect = $q->options->where('is_correct', true)->pluck('id')->first();

    Livewire::test(QuizPlayer::class, ['quiz' => $q->quiz])
        ->set('answers', [$q->id => [$oneCorrect]])
        ->call('submit');

    expect(QuizAttempt::first()->score)->toBe(50);
});

it('soal pilihan jamak: memilih opsi salah kena penalti', function () {
    // 2 benar + 2 salah. Pilih 2 benar + 1 salah → 2/2 − 1/2 = 0.5 → nilai 50.
    $q = makeSingleQuestionQuiz([
        ['text' => 'Benar A', 'correct' => true],
        ['text' => 'Benar B', 'correct' => true],
        ['text' => 'Salah A', 'correct' => false],
        ['text' => 'Salah B', 'correct' => false],
    ]);
    $correctIds = $q->options->where('is_correct', true)->pluck('id')->all();
    $oneWrong = $q->options->where('is_correct', false)->pluck('id')->first();

    Livewire::test(QuizPlayer::class, ['quiz' => $q->quiz])
        ->set('answers', [$q->id => [...$correctIds, $oneWrong]])
        ->call('submit');

    $attempt = QuizAttempt::first();
    expect($attempt->score)->toBe(50)
        // Satu baris jawaban per opsi yang dipilih (3 opsi).
        ->and(QuizAnswer::where('attempt_id', $attempt->id)->count())->toBe(3);
});

it('kuis tanpa soal tidak membuat attempt saat submit', function () {
    $module = Module::create([
        'title' => 'Modul Kosong '.uniqid(),
        'slug' => 'modul-kosong-'.uniqid(),
        'status' => 'published',
        'sort_order' => 1,
    ]);
    $quiz = Quiz::create(['module_id' => $module->id, 'passing_score' => 50, 'max_attempts' => 3]);

    Livewire::test(QuizPlayer::class, ['quiz' => $quiz])
        ->call('submit')
        ->assertSet('submitted', false);

    expect(QuizAttempt::count())->toBe(0);
});

it('memvalidasi soal yang belum dijawab tanpa membuat attempt', function () {
    $q = makeSingleQuestionQuiz([
        ['text' => 'Benar', 'correct' => true],
        ['text' => 'Salah', 'correct' => false],
    ]);

    Livewire::test(QuizPlayer::class, ['quiz' => $q->quiz])
        ->set('answers', [])
        ->call('submit')
        ->assertSet('submitted', false);

    expect(QuizAttempt::count())->toBe(0);
});
