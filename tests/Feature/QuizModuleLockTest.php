<?php

use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

function makeModuleForQuiz(): Module
{
    return Module::create([
        'judul' => 'Modul '.uniqid(),
        'status' => 'draft',
        'urutan' => 1,
    ]);
}

it('module_id bisa dipilih saat membuat kuis (enabled)', function () {
    Livewire::test(CreateQuiz::class)
        ->assertFormFieldEnabled('module_id');
});

it('setelah kuis dibuat, diarahkan ke halaman Edit (bukan index)', function () {
    $module = makeModuleForQuiz();

    Livewire::test(CreateQuiz::class)
        ->fillForm(['module_id' => $module->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(QuizResource::getUrl('edit', [
            'record' => Quiz::where('module_id', $module->id)->firstOrFail(),
        ]));
});

it('module_id terkunci saat mengedit kuis & tak berubah walau dipaksa', function () {
    $moduleA = makeModuleForQuiz();
    $moduleB = makeModuleForQuiz();
    $quiz = Quiz::create(['module_id' => $moduleA->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);

    Livewire::test(EditQuiz::class, ['record' => $quiz->getRouteKey()])
        ->assertFormFieldDisabled('module_id')
        // Walau state dipaksa ke modul lain, field disabled tak terdehidrasi → diabaikan.
        ->fillForm(['module_id' => $moduleB->id, 'nilai_lulus' => 80])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($quiz->refresh()->module_id)->toBe($moduleA->id)
        ->and($quiz->nilai_lulus)->toBe(80); // field lain tetap tersimpan
});

it('aksi "Tambah Kuis" dari daftar modul memprefill module_id di form buat kuis', function () {
    $module = makeModuleForQuiz();

    Livewire::withQueryParams(['module_id' => $module->id])
        ->test(CreateQuiz::class)
        ->assertFormSet(['module_id' => $module->id]);
});

it('daftar modul: aksi kuis mengarah ke create (tanpa kuis) atau edit (ber-kuis)', function () {
    $tanpaKuis = makeModuleForQuiz();
    $berKuis = makeModuleForQuiz();
    $quiz = Quiz::create(['module_id' => $berKuis->id, 'nilai_lulus' => 70, 'maks_percobaan' => 3]);

    Livewire::test(ListModules::class)
        ->assertTableActionExists('kelolaKuis')
        ->assertTableActionHasUrl('kelolaKuis', QuizResource::getUrl('create', ['module_id' => $tanpaKuis->id]), record: $tanpaKuis)
        ->assertTableActionHasUrl('kelolaKuis', QuizResource::getUrl('edit', ['record' => $quiz]), record: $berKuis);
});
