<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin nagari memberi akses UMKM ke warga (warga → umkm_owner)', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('beriAksesUmkm', $warga);

    expect($warga->refresh()->role)->toBe('umkm_owner');
});

it('admin nagari mencabut akses UMKM (umkm_owner → warga)', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $owner = User::factory()->umkmOwner()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('cabutAksesUmkm', $owner);

    expect($owner->refresh()->role)->toBe('warga');
});
