<?php

use App\Enums\ModuleProgressStatus;
use App\Enums\QuizAttemptStatus;
use App\Models\Desa;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Sinyal "kuis belum dikerjakan" untuk warga yang materinya sudah tuntas:
 * CTA menonjol di detail modul, CTA di kartu daftar modul, dan kartu "Kuis Lulus"
 * setelah tuntas — agar warga selalu tahu langkah tersisa.
 */
function makeCompletedModuleWithQuiz(User $warga): array
{
    Notification::fake(); // publish modul/kuis memicu notif — tak relevan di sini

    $module = Module::create(['judul' => 'Modul CTA '.uniqid(), 'status' => 'published', 'urutan' => 1]);
    $page = ModulePage::create([
        'module_id' => $module->id, 'judul' => 'Materi 1', 'urutan' => 1,
        'blocks' => [['type' => 'teks', 'data' => ['konten' => 'Isi.']]],
    ]);

    $quiz = Quiz::create(['module_id' => $module->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);
    $question = $quiz->questions()->create(['pertanyaan' => 'Soal 1', 'urutan' => 1]);
    $question->options()->createMany([
        ['teks_opsi' => 'Benar', 'is_correct' => true, 'urutan' => 1],
        ['teks_opsi' => 'Salah', 'is_correct' => false, 'urutan' => 2],
    ]);

    UserModuleProgress::create([
        'user_id' => $warga->id, 'module_id' => $module->id,
        'halaman_selesai' => [$page->id], 'status' => ModuleProgressStatus::Completed,
        'completed_at' => now(),
    ]);

    return [$module, $quiz];
}

it('detail modul selesai menonjolkan CTA "Kerjakan Kuis" selama kuis belum lulus', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    [$module] = makeCompletedModuleWithQuiz($warga);

    actingAs($warga)
        ->get(route('portal.modules.show', $module))
        ->assertOk()
        ->assertSee('Kerjakan Kuis')
        ->assertSee('Satu langkah lagi');
});

it('detail modul menampilkan kartu "Kuis Lulus" + nilai setelah lulus (tanpa CTA lagi)', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    [$module, $quiz] = makeCompletedModuleWithQuiz($warga);

    QuizAttempt::create([
        'user_id' => $warga->id, 'quiz_id' => $quiz->id,
        'nilai' => 85, 'status' => QuizAttemptStatus::Passed, 'submitted_at' => now(),
    ]);

    actingAs($warga)
        ->get(route('portal.modules.show', $module))
        ->assertOk()
        ->assertSee('Kuis Lulus')
        ->assertSee('85')
        ->assertDontSee('Kerjakan Kuis');
});

it('kartu daftar modul selesai ber-CTA "Kerjakan Kuis" selama kuis belum lulus', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    [$module, $quiz] = makeCompletedModuleWithQuiz($warga);

    actingAs($warga)
        ->get(route('portal.modules.index'))
        ->assertOk()
        ->assertSee('Kerjakan Kuis');

    QuizAttempt::create([
        'user_id' => $warga->id, 'quiz_id' => $quiz->id,
        'nilai' => 90, 'status' => QuizAttemptStatus::Passed, 'submitted_at' => now(),
    ]);

    actingAs($warga)
        ->get(route('portal.modules.index'))
        ->assertOk()
        ->assertDontSee('Kerjakan Kuis')
        ->assertSee('Lihat Kembali');
});

it('perayaan modul tuntas mengajak lanjut ke kuis bila kuisnya siap', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
    [$module] = makeCompletedModuleWithQuiz($warga);

    // Reset progres: materi terakhir belum selesai → complete memicu perayaan.
    UserModuleProgress::where('user_id', $warga->id)->where('module_id', $module->id)
        ->update(['halaman_selesai' => json_encode([]), 'status' => ModuleProgressStatus::InProgress, 'completed_at' => null]);

    $page = $module->pages()->first();

    actingAs($warga)
        ->post(route('portal.modules.pages.complete', [$module, $page]))
        ->assertRedirect(route('portal.modules.show', $module))
        ->assertSessionHas('celebrate', fn (array $c): bool => str_contains($c['message'], 'kerjakan kuisnya'));
});

it('beranda: modul ber-kuis-menunggu diprioritaskan di atas modul baru & selesai penuh, dgn label', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);

    [$pending] = makeCompletedModuleWithQuiz($warga);       // materi tuntas, kuis belum
    [$tuntas, $quizTuntas] = makeCompletedModuleWithQuiz($warga); // materi + kuis lulus
    QuizAttempt::create([
        'user_id' => $warga->id, 'quiz_id' => $quizTuntas->id,
        'nilai' => 100, 'status' => QuizAttemptStatus::Passed, 'submitted_at' => now(),
    ]);
    $baru = Module::create(['judul' => 'Modul Baru '.uniqid(), 'status' => 'published', 'urutan' => 9]);

    actingAs($warga)
        ->get(route('portal.home'))
        ->assertOk()
        ->assertSee('Kuis belum dikerjakan')
        // Urutan kartu "Lanjutkan Belajar": kuis-menunggu → belum dimulai → selesai penuh.
        ->assertSeeInOrder([$pending->judul, $baru->judul, $tuntas->judul]);
});
