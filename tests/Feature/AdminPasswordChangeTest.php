<?php

use App\Filament\Auth\EditProfile;
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
    $this->get(route('filament.admin.auth.profile'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('admin tanpa flag wajib-ganti tidak dialihkan', function () {
    actingAs(User::factory()->superAdmin()->create());

    $this->get('/admin')->assertSuccessful();
});

it('modal: ganti sandi melepas must_change; email & No.HP opsional', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id, 'email' => null]);
    $admin->issueOtp();
    actingAs($admin->fresh());

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'rahasia-baru-1')
        ->set('password_confirmation', 'rahasia-baru-1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $admin->refresh();
    expect($admin->must_change_password)->toBeFalse()
        ->and(Hash::check('rahasia-baru-1', $admin->password))->toBeTrue()
        ->and($admin->email)->toBeNull(); // email tak wajib
});

it('modal: kontak terisi → No.HP dinormalkan & email huruf kecil', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id, 'email' => null]);
    $admin->issueOtp();
    actingAs($admin->fresh());

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'rahasia-baru-1')
        ->set('password_confirmation', 'rahasia-baru-1')
        ->set('phone', '0812 3456 7890')
        ->set('email', 'Admin.X@Example.COM')
        ->call('save')
        ->assertHasNoErrors();

    $admin->refresh();
    expect($admin->phone)->toBe('6281234567890')
        ->and($admin->email)->toBe('admin.x@example.com');
});

it('modal: email yang sudah dipakai akun lain ditolak', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id, 'email' => null]);
    $admin->issueOtp();
    actingAs($admin->fresh());

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'rahasia-baru-1')
        ->set('password_confirmation', 'rahasia-baru-1')
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('profil admin: tanpa field Nama (fix), bisa isi No. HP (dinormalkan)', function () {
    $admin = User::factory()->desaAdmin()->create([
        'desa_id' => Desa::factory()->create()->id,
        'name' => 'Admin Nagari X',
    ]);
    actingAs($admin);

    Livewire::test(EditProfile::class)
        ->assertFormFieldDoesNotExist('name') // nama admin fix → tak bisa diubah di profil
        ->fillForm(['phone' => '0812 3456 7890'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($admin->refresh()->phone)->toBe('6281234567890') // dinormalkan 62xxx
        ->and($admin->name)->toBe('Admin Nagari X');        // nama tak berubah
});
