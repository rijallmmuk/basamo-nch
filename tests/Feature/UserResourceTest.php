<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Nagari;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'nagari_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('super_admin dapat membuat admin nagari, password ter-hash & role tersinkron', function () {
    actingAs(User::factory()->superAdmin()->create());
    $nagari = Nagari::factory()->create(['nama' => 'Nagari Uji', 'kode' => 'NCH-TEST']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Budi Admin',
            'username' => 'budiadmin',
            'email' => 'budi@nagari.test',
            'role' => 'nagari_admin',
            'nagari_id' => $nagari->id,
            'status' => 'active',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'budi@nagari.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->nagari_id)->toBe($nagari->id)
        ->and($user->hasRole('nagari_admin'))->toBeTrue()
        ->and(Hash::check('rahasia123', $user->password))->toBeTrue();
});

it('nagari_admin hanya melihat pengguna di nagarinya sendiri', function () {
    $nagariA = Nagari::factory()->create(['nama' => 'Nagari A', 'kode' => 'A1']);
    $nagariB = Nagari::factory()->create(['nama' => 'Nagari B', 'kode' => 'B1']);

    actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $nagariA->id]));
    $wargaA = User::factory()->warga()->create(['nagari_id' => $nagariA->id]);
    $wargaB = User::factory()->warga()->create(['nagari_id' => $nagariB->id]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$wargaA])
        ->assertCanNotSeeTableRecords([$wargaB]);
});

it('nagari_admin tidak bisa membuat akun berperan admin', function () {
    $nagari = Nagari::factory()->create(['nama' => 'Nagari A', 'kode' => 'A1']);
    actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]));

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
