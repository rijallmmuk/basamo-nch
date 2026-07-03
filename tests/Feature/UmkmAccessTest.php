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

it('memberi ulang akses mengaktifkan kembali lapak yang dinonaktifkan saat dicabut', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create([
        'desa_id' => $desa->id, 'user_id' => $owner->id, 'status' => 'active',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)->callTableAction('cabutAksesUmkm', $owner);
    expect($profile->refresh()->status->value)->toBe('inactive');

    // Janji modal cabut: "bisa diaktifkan lagi bila akses dipulihkan".
    Livewire::test(ListUsers::class)->callTableAction('beriAksesUmkm', $owner->refresh());

    expect($owner->refresh()->hasUmkmAccess())->toBeTrue()
        ->and($profile->refresh()->status->value)->toBe('active');
});

it('menghapus lapak (arsip maupun permanen) ikut mencabut akses UMKM; pulihkan mengembalikannya', function () {
    $desa = Desa::factory()->create();
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create([
        'desa_id' => $desa->id, 'user_id' => $owner->id, 'status' => 'active',
    ]);

    $profile->delete(); // arsip
    expect($owner->refresh()->hasUmkmAccess())->toBeFalse();

    $profile->restore();
    expect($owner->refresh()->hasUmkmAccess())->toBeTrue();

    $profile->forceDelete(); // permanen
    expect($owner->refresh()->hasUmkmAccess())->toBeFalse();
});

it('lapak terarsip dipakai ulang saat warga mengisi profil lagi (tanpa bentrok unik user_id)', function () {
    $desa = Desa::factory()->create();
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id, 'must_change_password' => false]);
    $lama = UmkmProfile::factory()->create(['desa_id' => $desa->id, 'user_id' => $owner->id]);

    $lama->delete(); // akses ikut tercabut (event deleted)
    $owner->refresh()->update(['umkm_access_granted_at' => now()]); // admin beri akses lagi

    $this->actingAs($owner)
        ->post(route('portal.umkm.profile.store'), [
            'nama_usaha' => 'Lapak Baru',
            'whatsapp' => '08123456789',
            'alamat' => 'Jorong Baru, dekat surau',
        ])
        ->assertRedirect(route('portal.umkm.index'));

    expect(UmkmProfile::withTrashed()->where('user_id', $owner->id)->count())->toBe(1)
        ->and($lama->refresh()->trashed())->toBeFalse()
        ->and($lama->nama_usaha)->toBe('Lapak Baru');
});
