<?php

use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\PagesRelationManager;
use App\Models\Module;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    actingAs(User::factory()->superAdmin()->create());
});

function moduleForPages(): Module
{
    return Module::create(['judul' => 'M '.uniqid(), 'slug' => 'm-'.uniqid(), 'status' => 'draft', 'urutan' => 1]);
}

function createPageAction(Module $module, array $data): Testable
{
    return Livewire::test(PagesRelationManager::class, [
        'ownerRecord' => $module,
        'pageClass' => EditModule::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData($data)
        ->callMountedTableAction();
}

// Regresi bug: ganti tipe tak memunculkan field karena $get('tipe') = enum, dibanding string.
it('tipe video: field url_video tampil, wajib, & tersimpan', function () {
    $module = moduleForPages();

    createPageAction($module, ['judul' => 'Video', 'tipe' => 'video', 'url_video' => 'https://youtu.be/abcdefghijk'])
        ->assertHasNoTableActionErrors();

    expect($module->pages()->where('tipe', 'video')->value('url_video'))->toBe('https://youtu.be/abcdefghijk');
});

it('tipe video tanpa url_video → error wajib (required kondisional aktif)', function () {
    createPageAction(moduleForPages(), ['judul' => 'Video', 'tipe' => 'video'])
        ->assertHasTableActionErrors(['url_video']);
});

it('tipe text tanpa konten → error wajib', function () {
    createPageAction(moduleForPages(), ['judul' => 'Teks', 'tipe' => 'text'])
        ->assertHasTableActionErrors(['konten']);
});

it('tipe text: konten tersimpan, url_video & path_file null', function () {
    $module = moduleForPages();

    createPageAction($module, ['judul' => 'Teks', 'tipe' => 'text', 'konten' => '<p>materi</p>'])
        ->assertHasNoTableActionErrors();

    $page = $module->pages()->where('tipe', 'text')->first();
    expect($page->konten)->toContain('materi')
        ->and($page->url_video)->toBeNull()
        ->and($page->path_file)->toBeNull();
});
