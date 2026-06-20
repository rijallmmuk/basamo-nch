<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Nagari;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin nagari memberi akses UMKM ke warga (tanpa mengubah peran)', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('beriAksesUmkm', $warga);

    $warga->refresh();
    expect($warga->role)->toBe('warga')
        ->and($warga->hasUmkmAccess())->toBeTrue();
});

it('admin nagari mencabut akses UMKM dan menonaktifkan lapaknya', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $owner = User::factory()->umkmOwner()->create(['nagari_id' => $nagari->id]);
    $profile = UmkmProfile::factory()->create([
        'nagari_id' => $nagari->id, 'user_id' => $owner->id, 'status' => 'active',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('cabutAksesUmkm', $owner);

    $owner->refresh();
    expect($owner->role)->toBe('warga')
        ->and($owner->hasUmkmAccess())->toBeFalse()
        // Lapak keluar dari katalog publik (U3).
        ->and($profile->refresh()->status)->toBe('inactive');
});
