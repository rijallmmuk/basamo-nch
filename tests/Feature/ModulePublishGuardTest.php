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
        ->fillForm(['judul' => 'Modul Tanpa Materi', 'status' => 'published'])
        ->call('create')
        ->assertHasFormErrors(['status']);
});

it('bisa publish modul yang sudah punya materi', function () {
    $module = Module::create([
        'judul' => 'Modul Siap',
        'slug' => 'modul-siap-'.uniqid(),
        'status' => 'draft',
        'urutan' => 1,
    ]);
    ModulePage::create([
        'module_id' => $module->id,
        'judul' => 'Materi 1',
        'tipe' => 'text',
        'konten' => 'Isi materi.',
        'urutan' => 1,
    ]);

    Livewire::test(EditModule::class, ['record' => $module->getRouteKey()])
        ->fillForm(['status' => 'published'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($module->refresh()->status->value)->toBe('published');
});
