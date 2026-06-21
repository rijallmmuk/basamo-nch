<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Desa;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin desa memberi akses UMKM ke warga (tanpa mengubah peran)', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('beriAksesUmkm', $warga);

    $warga->refresh();
    expect($warga->role)->toBe('warga')
        ->and($warga->hasUmkmAccess())->toBeTrue();
});

it('admin desa mencabut akses UMKM dan menonaktifkan lapaknya', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create([
        'desa_id' => $desa->id, 'user_id' => $owner->id, 'status' => 'active',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('cabutAksesUmkm', $owner);

    $owner->refresh();
    expect($owner->role)->toBe('warga')
        ->and($owner->hasUmkmAccess())->toBeFalse()
        // Lapak keluar dari katalog publik (U3).
        ->and($profile->refresh()->status->value)->toBe('inactive');
});
