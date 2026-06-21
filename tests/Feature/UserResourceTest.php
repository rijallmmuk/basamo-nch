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
    $desa = Desa::factory()->create(['nama' => 'Desa Uji', 'kode' => 'NCH-TEST']);

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
    $desaA = Desa::factory()->create(['nama' => 'Desa A', 'kode' => 'A1']);
    $desaB = Desa::factory()->create(['nama' => 'Desa B', 'kode' => 'B1']);

    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]));
    $wargaA = User::factory()->warga()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$wargaA])
        ->assertCanNotSeeTableRecords([$wargaB]);
});

it('desa_admin tidak bisa membuat akun berperan admin', function () {
    $desa = Desa::factory()->create(['nama' => 'Desa A', 'kode' => 'A1']);
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Curang',
            'username' => 'curang',
            'email' => 'curang@a.test',
            'role' => 'super_admin',
            'status' => 'active',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->call('create')
        ->assertHasFormErrors(['role']);
});

it('aksi hapus disembunyikan untuk akun sendiri', function () {
    $admin = User::factory()->superAdmin()->create();
    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('delete', $admin);
});
