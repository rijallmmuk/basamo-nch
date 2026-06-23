<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Desa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('super_admin dapat membuat admin desa, password ter-hash & role tersinkron', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create(['nama' => 'Desa Uji']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Budi Admin',
            'username' => 'budiadmin',
            'email' => 'budi@desa.test',
            'role' => 'desa_admin',
            'desa_id' => $desa->id,
            'status' => 'active',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'budi@desa.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->desa_id)->toBe($desa->id)
        ->and($user->hasRole('desa_admin'))->toBeTrue()
        ->and(Hash::check('rahasia123', $user->password))->toBeTrue();
});

it('desa_admin hanya melihat pengguna di desanya sendiri', function () {
    $desaA = Desa::factory()->create(['nama' => 'Desa A']);
    $desaB = Desa::factory()->create(['nama' => 'Desa B']);

    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]));
    $wargaA = User::factory()->warga()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$wargaA])
        ->assertCanNotSeeTableRecords([$wargaB]);
});

it('desa_admin: form warga tanpa pilihan peran, akun dibuat sebagai warga', function () {
    $desa = Desa::factory()->create(['nama' => 'Desa A']);
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    // Pilihan peran disembunyikan untuk admin desa (hanya kelola warga).
    Livewire::test(CreateUser::class)
        ->assertFormFieldIsHidden('role')
        ->fillForm([
            'name' => 'Warga Baru',
            'nik' => '3201000000000999',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Peran dipaksa warga di server (defense-in-depth) + ter-scope ke desa admin.
    $warga = User::where('nik', '3201000000000999')->first();
    expect($warga->role)->toBe('warga')
        ->and($warga->desa_id)->toBe($desa->id);
});

it('aksi hapus disembunyikan untuk akun sendiri', function () {
    $admin = User::factory()->superAdmin()->create();
    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('delete', $admin);
});
