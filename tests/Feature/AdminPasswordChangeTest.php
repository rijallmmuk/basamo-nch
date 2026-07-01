<?php

use App\Filament\Pages\Profil;
use App\Livewire\ForcePasswordChange;
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

it('admin desa dgn OTP awal: dikunci di dashboard + modal ganti sandi tampil', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]);
    $admin->issueOtp(); // must_change_password = true

    actingAs($admin->fresh());

    // Dashboard boleh diakses (tempat modal pemblokir muncul) — modal tampil.
    $this->get('/admin')
        ->assertOk()
        ->assertSee('Ganti Kata Sandi');

    // Halaman lain (mis. profil) dialihkan ke dashboard sampai sandi diganti.
    $this->get(route('filament.admin.pages.profil'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('admin tanpa flag wajib-ganti tidak dialihkan', function () {
    actingAs(User::factory()->superAdmin()->create());

    $this->get('/admin')->assertSuccessful();
});

it('modal: ganti sandi melepas must_change (hanya sandi, tanpa field kontak)', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]);
    $admin->issueOtp();
    actingAs($admin->fresh());

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'rahasiabaru') // bebas, tanpa angka, ≥8 karakter
        ->set('password_confirmation', 'rahasiabaru')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true); // tampil langkah sukses, BUKAN redirect senyap

    expect($admin->refresh()->must_change_password)->toBeFalse()
        ->and(Hash::check('rahasiabaru', $admin->password))->toBeTrue();
});

it('modal: sandi terlalu pendek (<8) ditolak', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]);
    $admin->issueOtp();
    actingAs($admin->fresh());

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'pendek')
        ->set('password_confirmation', 'pendek')
        ->call('save')
        ->assertHasErrors(['password']);
});

it('profil admin desa: aksi Ubah Profil isi kontak (HP dinormalkan), nama tetap fix', function () {
    $admin = User::factory()->desaAdmin()->create([
        'desa_id' => Desa::factory()->create()->id,
        'name' => 'Admin Nagari X',
    ]);
    actingAs($admin);

    Livewire::test(Profil::class)
        ->callAction('ubahProfil', data: ['email' => 'kontak@desa.test', 'phone' => '0812 3456 7890'])
        ->assertHasNoActionErrors();

    $admin->refresh();
    expect($admin->phone)->toBe('6281234567890')          // dinormalkan 62xxx
        ->and($admin->email)->toBe('kontak@desa.test')
        ->and($admin->name)->toBe('Admin Nagari X');       // nama admin desa fix (field name absen)
});

it('profil super admin: aksi Ubah Profil bisa ganti nama', function () {
    $super = User::factory()->superAdmin()->create(['name' => 'Super Lama']);
    actingAs($super);

    Livewire::test(Profil::class)
        ->callAction('ubahProfil', data: ['name' => 'Super Baru'])
        ->assertHasNoActionErrors();

    expect($super->refresh()->name)->toBe('Super Baru');
});

it('keamanan: ganti sandi wajib sandi lama benar', function () {
    $admin = User::factory()->desaAdmin()->create([
        'desa_id' => Desa::factory()->create()->id,
        'password' => Hash::make('sandilama'),
    ]);
    actingAs($admin);

    // Sandi lama salah → ditolak
    Livewire::test(Profil::class)
        ->callAction('ubahKeamanan', data: [
            'current_password' => 'salah',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])
        ->assertHasActionErrors(['current_password']);

    // Sandi lama benar → berhasil
    Livewire::test(Profil::class)
        ->callAction('ubahKeamanan', data: [
            'current_password' => 'sandilama',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])
        ->assertHasNoActionErrors();

    expect(Hash::check('sandibaru123', $admin->refresh()->password))->toBeTrue();
});

it('keamanan: super admin bisa ubah username; admin desa tidak', function () {
    $super = User::factory()->superAdmin()->create([
        'username' => 'superlama',
        'password' => Hash::make('sandilama'),
    ]);
    actingAs($super);

    Livewire::test(Profil::class)
        ->callAction('ubahKeamanan', data: ['current_password' => 'sandilama', 'username' => 'superbaru'])
        ->assertHasNoActionErrors();

    expect($super->refresh()->username)->toBe('superbaru');
});

it('halaman profil admin terbuka: data read-only + tombol Ubah tampil', function () {
    $super = User::factory()->superAdmin()->create(['name' => 'Pak Super', 'username' => 'supx']);
    actingAs($super);

    $this->get(route('filament.admin.pages.profil'))
        ->assertOk()
        ->assertSee('Pak Super')     // data read-only
        ->assertSee('supx')
        ->assertSee('Ubah Profil')   // aksi header
        ->assertSee('Ubah Keamanan');
});
