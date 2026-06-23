<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Desa;
use App\Models\Penduduk;
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

it('super_admin membuat warga lengkap dengan penduduk tertaut', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();

    // Pilih desa dulu (memilih desa mereset wilayah), baru wilayahnya — seperti alur nyata.
    $data = wargaFormData($desa, '3201000000000123', ['role' => 'warga', 'desa_id' => $desa->id]);
    $unitId = $data['desa_unit_id'];
    unset($data['desa_unit_id']);

    Livewire::test(CreateUser::class)
        ->fillForm($data)
        ->fillForm(['desa_unit_id' => $unitId])
        ->call('create')
        ->assertHasNoFormErrors();

    $warga = User::where('nik', '3201000000000123')->first();

    expect($warga)->not->toBeNull()
        ->and($warga->role)->toBe('warga')
        ->and($warga->desa_id)->toBe($desa->id)
        ->and($warga->penduduk)->not->toBeNull()
        ->and($warga->penduduk->nama)->toBe($warga->name)
        ->and($warga->penduduk->desa_id)->toBe($desa->id)
        ->and($warga->initial_otp)->toBeNull(); // OTP tidak auto-generate
});

it('hapus warga (soft) menyisakan identitas penduduk; bisa dipulihkan', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'nik' => '3201000000000124']);
    $penduduk = Penduduk::create(['nik' => '3201000000000124', 'nama' => $warga->name, 'desa_id' => $desa->id]);
    $warga->forceFill(['penduduk_id' => $penduduk->id])->save();

    $warga->delete();

    expect($warga->fresh()->trashed())->toBeTrue()
        ->and(Penduduk::find($penduduk->id))->not->toBeNull(); // identitas tetap

    $warga->restore();
    expect($warga->fresh()->trashed())->toBeFalse();
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
        ->fillForm(wargaFormData($desa, '3201000000000999'))
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
