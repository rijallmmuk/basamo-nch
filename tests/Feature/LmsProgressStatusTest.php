<?php

use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\LmsProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regresi: getModuleStatus mem-`match` enum ModuleProgressStatus (bukan string).
 * Pernah bug — arm string tak cocok dgn nilai enum → semua modul "available".
 */
function progressModule(): Module
{
    $module = Module::create([
        'title' => 'Modul '.uniqid(),
        'slug' => 'modul-'.uniqid(),
        'status' => 'published',
        'sort_order' => 1,
    ]);

    ModulePage::create([
        'module_id' => $module->id,
        'title' => 'Materi',
        'type' => 'text',
        'content' => 'Isi.',
        'sort_order' => 1,
    ]);

    return $module;
}

it('getModuleStatus mengembalikan available tanpa progres', function () {
    $user = User::factory()->warga()->create();
    $module = progressModule();

    expect(app(LmsProgressService::class)->getModuleStatus($user, $module))->toBe('available');
});

it('getModuleStatus mencerminkan in_progress dan completed', function () {
    $service = app(LmsProgressService::class);
    $user = User::factory()->warga()->create();

    $sedang = progressModule();
    UserModuleProgress::create([
        'user_id' => $user->id, 'module_id' => $sedang->id,
        'status' => 'in_progress', 'pages_completed' => [],
    ]);

    $selesai = progressModule();
    UserModuleProgress::create([
        'user_id' => $user->id, 'module_id' => $selesai->id,
        'status' => 'completed', 'pages_completed' => [], 'completed_at' => now(),
    ]);

    expect($service->getModuleStatus($user, $sedang))->toBe('in_progress')
        ->and($service->getModuleStatus($user, $selesai))->toBe('completed');
});

it('getModuleStatus mengunci modul saat prasyarat belum selesai', function () {
    $user = User::factory()->warga()->create();

    $prasyarat = progressModule();
    $lanjutan = progressModule();
    $lanjutan->update(['prerequisite_module_id' => $prasyarat->id]);

    expect(app(LmsProgressService::class)->getModuleStatus($user, $lanjutan))->toBe('locked');
});
