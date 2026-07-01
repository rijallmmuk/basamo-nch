<?php

use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('tamu membuka /admin diarahkan ke halaman login gabungan', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('halaman login gabungan bisa dibuka', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('NIK, Username, atau Email');
});

it('super admin login (username) diarahkan ke panel admin', function () {
    $super = User::factory()->superAdmin()->create([
        'username' => 'superx',
        'password' => Hash::make('rahasia123'),
    ]);

    $this->post(route('login'), ['login' => 'superx', 'password' => 'rahasia123'])
        ->assertRedirect('/admin');

    expect(auth()->id())->toBe($super->id);
});

it('admin login via email diarahkan ke panel admin', function () {
    $admin = User::factory()->desaAdmin()->create([
        'desa_id' => Desa::factory()->create()->id,
        'email' => 'admin@nagari.test',
        'password' => Hash::make('rahasia123'),
    ]);

    $this->post(route('login'), ['login' => 'admin@nagari.test', 'password' => 'rahasia123'])
        ->assertRedirect('/admin');

    expect(auth()->id())->toBe($admin->id);
});

it('warga login (NIK) diarahkan ke portal', function () {
    $warga = User::factory()->warga()->create([
        'desa_id' => Desa::factory()->create()->id,
        'nik' => '3201010101010001',
        'password' => Hash::make('rahasia123'),
    ]);

    $this->post(route('login'), ['login' => '3201010101010001', 'password' => 'rahasia123'])
        ->assertRedirect(route('portal.home'));

    expect(auth()->id())->toBe($warga->id);
});

it('akun nonaktif ditolak walau sandi benar', function () {
    $warga = User::factory()->warga()->create([
        'desa_id' => Desa::factory()->create()->id,
        'nik' => '3201010101010002',
        'password' => Hash::make('rahasia123'),
        'status' => 'inactive',
    ]);

    $this->post(route('login'), ['login' => '3201010101010002', 'password' => 'rahasia123'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('sandi salah ditolak', function () {
    User::factory()->superAdmin()->create(['username' => 'supery', 'password' => Hash::make('rahasia123')]);

    $this->post(route('login'), ['login' => 'supery', 'password' => 'salah'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('logout admin berhasil tanpa error dan sesi berakhir', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('filament.admin.auth.logout'))
        ->assertRedirect();

    $this->assertGuest();
});

it('logout warga mengarah ke halaman login gabungan', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);

    $this->actingAs($warga)
        ->post(route('portal.logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('warga tetap diarahkan ke portal walau ada intended /admin sebelumnya', function () {
    $warga = User::factory()->warga()->create([
        'desa_id' => Desa::factory()->create()->id,
        'nik' => '3201010101010009',
        'password' => Hash::make('rahasia123'),
    ]);

    // Tamu membuka /admin lebih dulu → menyimpan intended '/admin' di sesi.
    $this->get('/admin')->assertRedirect(route('login'));

    // Warga login → HARUS ke portal, bukan terlempar ke /admin (yang akan ditolak).
    $this->post(route('login'), ['login' => '3201010101010009', 'password' => 'rahasia123'])
        ->assertRedirect(route('portal.home'));
});
