<?php

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
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
