<?php

use App\Models\Desa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('admin desa dgn OTP awal dipaksa ke halaman profil', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]);
    $admin->issueOtp(); // must_change_password = true

    actingAs($admin->fresh());

    // Akses panel dialihkan ke halaman profil sampai sandi diganti.
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.profile'));

    // Halaman profil sendiri tidak ikut dialihkan (tak ada loop).
    $this->get(route('filament.admin.auth.profile'))->assertOk();
});

it('admin tanpa flag wajib-ganti tidak dialihkan', function () {
    actingAs(User::factory()->superAdmin()->create());

    $this->get('/admin')->assertSuccessful();
});
