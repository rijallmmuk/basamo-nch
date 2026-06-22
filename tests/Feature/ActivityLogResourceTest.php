<?php

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\Desa;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('super_admin dapat mengakses Log Aktivitas', function () {
    $user = User::factory()->superAdmin()->create();

    // Pancing satu entri aktivitas agar tabel merender baris.
    Module::create([
        'judul' => 'Modul Log '.uniqid(),
        'slug' => 'modul-log-'.uniqid(),
        'status' => 'draft',
        'urutan' => 1,
    ]);

    $this->actingAs($user)
        ->get(ActivityLogResource::getUrl('index'))
        ->assertSuccessful();
});

it('desa_admin tidak dapat mengakses Log Aktivitas', function () {
    $desa = Desa::factory()->create();
    $user = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);

    $this->actingAs($user)
        ->get(ActivityLogResource::getUrl('index'))
        ->assertForbidden();
});

it('mencatat aktivitas saat modul dibuat', function () {
    Module::create([
        'judul' => 'Modul Audit '.uniqid(),
        'slug' => 'modul-audit-'.uniqid(),
        'status' => 'draft',
        'urutan' => 1,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'modul',
        'event' => 'created',
    ]);
});
