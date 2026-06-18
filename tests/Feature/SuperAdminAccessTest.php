<?php

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminPanel()
{
    return filament()->getPanel('admin');
}

it('super_admin aktif dapat mengakses panel admin', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->canAccessPanel(adminPanel()))->toBeTrue();
});

it('super_admin nonaktif diblokir dari panel admin', function () {
    $user = User::factory()->superAdmin()->inactive()->create();

    expect($user->canAccessPanel(adminPanel()))->toBeFalse();
});

it('super_admin melewati semua ability berdasarkan kolom role saja (tanpa Spatie role)', function () {
    $user = User::factory()->superAdmin()->create();

    expect(Gate::forUser($user)->check('viewAny', Module::class))->toBeTrue()
        ->and(Gate::forUser($user)->check('viewAny', Role::class))->toBeTrue()
        ->and(Gate::forUser($user)->check('delete', Module::class))->toBeTrue();
});

it('nagari_admin dapat mengelola modul tetapi tidak role', function () {
    $user = User::factory()->nagariAdmin()->create();

    expect(Gate::forUser($user)->check('viewAny', Module::class))->toBeTrue()
        ->and(Gate::forUser($user)->check('create', Module::class))->toBeTrue()
        ->and(Gate::forUser($user)->check('viewAny', Role::class))->toBeFalse();
});

it('warga & umkm_owner tidak bisa akses panel maupun resource admin', function () {
    foreach ([User::factory()->warga()->create(), User::factory()->umkmOwner()->create()] as $user) {
        expect($user->canAccessPanel(adminPanel()))->toBeFalse()
            ->and(Gate::forUser($user)->check('viewAny', Module::class))->toBeFalse();
    }
});

it('mengubah kolom role otomatis menyinkronkan Spatie role', function () {
    foreach (['super_admin', 'nagari_admin', 'warga', 'umkm_owner'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $user = User::factory()->nagariAdmin()->create();
    expect($user->hasRole('nagari_admin'))->toBeTrue();

    $user->update(['role' => 'warga']);
    expect($user->fresh()->hasRole('warga'))->toBeTrue()
        ->and($user->fresh()->hasRole('nagari_admin'))->toBeFalse();
});
