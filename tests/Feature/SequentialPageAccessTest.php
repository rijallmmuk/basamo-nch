<?php

use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use App\Services\LmsProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Materi modul wajib dibaca berurutan — warga tidak boleh melompati materi yang
 * belum diselesaikan (baik via URL langsung maupun POST "tandai selesai").
 */
function modulWithPages(int $count = 3): Module
{
    $module = Module::create([
        'judul' => 'Modul '.uniqid(),
        'slug' => 'modul-'.uniqid(),
        'status' => 'published',
        'urutan' => 1,
    ]);

    for ($i = 1; $i <= $count; $i++) {
        ModulePage::create([
            'module_id' => $module->id,
            'judul' => "Materi $i",
            'blocks' => [['type' => 'teks', 'data' => ['konten' => "Isi $i."]]],
            'urutan' => $i,
        ]);
    }

    return $module->refresh();
}

it('isPageAccessible hanya membuka materi pertama saat belum ada progres', function () {
    $service = app(LmsProgressService::class);
    $module = modulWithPages();
    [$p1, $p2, $p3] = $module->pages->all();

    expect($service->isPageAccessible($module, $p1, []))->toBeTrue()
        ->and($service->isPageAccessible($module, $p2, []))->toBeFalse()
        ->and($service->isPageAccessible($module, $p3, []))->toBeFalse();
});

it('isPageAccessible membuka materi berikutnya setelah materi sebelumnya selesai', function () {
    $service = app(LmsProgressService::class);
    $module = modulWithPages();
    [$p1, $p2, $p3] = $module->pages->all();

    expect($service->isPageAccessible($module, $p2, [$p1->id]))->toBeTrue()
        ->and($service->isPageAccessible($module, $p3, [$p1->id]))->toBeFalse()
        ->and($service->isPageAccessible($module, $p3, [$p1->id, $p2->id]))->toBeTrue();
});

it('mengalihkan warga yang membuka materi terkunci ke materi yang seharusnya dibaca', function () {
    $user = User::factory()->warga()->create();
    $module = modulWithPages();
    [$p1, $p2] = $module->pages->all();

    $this->actingAs($user)
        ->get(route('portal.modules.pages.show', [$module, $p2]))
        ->assertRedirect(route('portal.modules.pages.show', [$module, $p1]))
        ->assertSessionHas('error');
});

it('mengizinkan materi pertama dibuka langsung', function () {
    $user = User::factory()->warga()->create();
    $module = modulWithPages();
    $p1 = $module->pages->first();

    $this->actingAs($user)
        ->get(route('portal.modules.pages.show', [$module, $p1]))
        ->assertOk();
});

it('membuka materi berikutnya setelah menandai materi pertama selesai', function () {
    $user = User::factory()->warga()->create();
    $module = modulWithPages();
    [$p1, $p2] = $module->pages->all();

    $this->actingAs($user)
        ->post(route('portal.modules.pages.complete', [$module, $p1]))
        ->assertRedirect(route('portal.modules.pages.show', [$module, $p2]));

    $this->actingAs($user)
        ->get(route('portal.modules.pages.show', [$module, $p2]))
        ->assertOk();
});

it('menolak menandai selesai materi yang dilewati', function () {
    $user = User::factory()->warga()->create();
    $module = modulWithPages();
    [$p1, $p2] = $module->pages->all();

    $this->actingAs($user)
        ->post(route('portal.modules.pages.complete', [$module, $p2]))
        ->assertRedirect(route('portal.modules.show', $module))
        ->assertSessionHas('error');

    expect($user->moduleProgress()->where('module_id', $module->id)->first()?->halaman_selesai ?? [])
        ->not->toContain($p2->id);
});
