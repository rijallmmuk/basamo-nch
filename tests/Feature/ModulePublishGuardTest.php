<?php

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\PagesRelationManager;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/** Modul + satu materi, status sesuai argumen. */
function moduleWithOnePage(string $status): array
{
    $module = Module::create([
        'judul' => 'Modul '.uniqid(),
        'slug' => 'modul-'.uniqid(),
        'status' => $status,
        'urutan' => 1,
    ]);
    $page = ModulePage::create([
        'module_id' => $module->id,
        'judul' => 'Materi 1',
        'tipe' => 'text',
        'konten' => 'Isi.',
        'urutan' => 1,
    ]);

    return [$module, $page];
}

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

it('tidak bisa menghapus materi terakhir pada modul published', function () {
    [$module, $page] = moduleWithOnePage('published');

    Livewire::test(PagesRelationManager::class, [
        'ownerRecord' => $module,
        'pageClass' => EditModule::class,
    ])->callTableAction('delete', $page);

    expect(ModulePage::find($page->id))->not->toBeNull(); // guard meng-halt
});

it('bisa menghapus materi terakhir bila modul masih draft', function () {
    [$module, $page] = moduleWithOnePage('draft');

    Livewire::test(PagesRelationManager::class, [
        'ownerRecord' => $module,
        'pageClass' => EditModule::class,
    ])->callTableAction('delete', $page);

    expect(ModulePage::find($page->id))->toBeNull(); // terhapus
});
