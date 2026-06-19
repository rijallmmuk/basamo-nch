<?php

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('super_admin dapat mengakses Log Aktivitas', function () {
    $user = User::factory()->superAdmin()->create();

    // Pancing satu entri aktivitas agar tabel merender baris.
    Module::create([
        'title' => 'Modul Log '.uniqid(),
        'slug' => 'modul-log-'.uniqid(),
        'status' => 'draft',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->get(ActivityLogResource::getUrl('index'))
        ->assertSuccessful();
});

it('nagari_admin tidak dapat mengakses Log Aktivitas', function () {
    $nagari = Nagari::factory()->create();
    $user = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($user)
        ->get(ActivityLogResource::getUrl('index'))
        ->assertForbidden();
});

it('mencatat aktivitas saat modul dibuat', function () {
    Module::create([
        'title' => 'Modul Audit '.uniqid(),
        'slug' => 'modul-audit-'.uniqid(),
        'status' => 'draft',
        'sort_order' => 1,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'modul',
        'event' => 'created',
    ]);
});
