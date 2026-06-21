<?php

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('tidak bisa langsung publish modul tanpa materi', function () {
    Livewire::test(CreateModule::class)
        ->fillForm(['title' => 'Modul Tanpa Materi', 'status' => 'published'])
        ->call('create')
        ->assertHasFormErrors(['status']);
});

it('bisa publish modul yang sudah punya materi', function () {
    $module = Module::create([
        'title' => 'Modul Siap',
        'slug' => 'modul-siap-'.uniqid(),
        'status' => 'draft',
        'sort_order' => 1,
    ]);
    ModulePage::create([
        'module_id' => $module->id,
        'title' => 'Materi 1',
        'type' => 'text',
        'content' => 'Isi materi.',
        'sort_order' => 1,
    ]);

    Livewire::test(EditModule::class, ['record' => $module->getRouteKey()])
        ->fillForm(['status' => 'published'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($module->refresh()->status->value)->toBe('published');
});
