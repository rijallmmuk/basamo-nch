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

it('menyimpan halaman dengan satu blok teks', function () {
    $module = moduleForPages();

    createPageAction($module, [
        'judul' => 'Teks',
        'blocks' => [['type' => 'teks', 'data' => ['konten' => '<p>materi</p>']]],
    ])->assertHasNoTableActionErrors();

    $page = $module->pages()->first();
    expect($page->blocks)->toHaveCount(1)
        ->and($page->blocks[0]['type'])->toBe('teks')
        ->and($page->blocks[0]['data']['konten'])->toContain('materi');
});

it('menyimpan halaman campuran teks + video terurut', function () {
    $module = moduleForPages();

    createPageAction($module, [
        'judul' => 'Campuran',
        'blocks' => [
            ['type' => 'teks', 'data' => ['konten' => '<p>tonton dulu</p>']],
            ['type' => 'video', 'data' => ['url' => 'https://youtu.be/abcdefghijk', 'caption' => null]],
        ],
    ])->assertHasNoTableActionErrors();

    $page = $module->pages()->first();
    expect($page->blocks)->toHaveCount(2)
        ->and($page->blocks[0]['type'])->toBe('teks')
        ->and($page->blocks[1]['type'])->toBe('video')
        ->and($page->blocks[1]['data']['url'])->toBe('https://youtu.be/abcdefghijk');
});

it('menolak halaman tanpa blok sama sekali (minItems)', function () {
    $module = moduleForPages();

    createPageAction($module, ['judul' => 'Kosong', 'blocks' => []])
        ->assertHasTableActionErrors();

    expect($module->pages()->count())->toBe(0);
});
