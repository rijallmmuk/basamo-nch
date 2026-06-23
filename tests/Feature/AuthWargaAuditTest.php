<?php

use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeWarga(array $overrides = []): User
{
    $desa = Desa::factory()->create();

    return User::factory()->warga()->create(array_merge([
        'desa_id' => $desa->id,
        'nik' => '3201000000000999',
        'password' => 'rahasia-warga',
        'must_change_password' => false,
    ], $overrides));
}

// ── A1: warga nonaktif tidak boleh login portal ──────────────────────
it('menolak login warga berstatus nonaktif', function () {
    makeWarga(['status' => 'inactive']);

    $this->from(route('portal.login'))
        ->post(route('portal.login'), [
            'login' => '3201000000000999',
            'password' => 'rahasia-warga',
        ])
        ->assertRedirect(route('portal.login'))
        ->assertSessionHasErrors('login');

    expect(auth()->check())->toBeFalse();
});

it('mengizinkan login warga aktif', function () {
    makeWarga(['status' => 'active']);

    $this->post(route('portal.login'), [
        'login' => '3201000000000999',
        'password' => 'rahasia-warga',
    ])->assertRedirect(route('portal.home'));

    expect(auth()->check())->toBeTrue();
});

it('mengeluarkan warga yang dinonaktifkan saat sesi berjalan', function () {
    $warga = makeWarga(['status' => 'active']);

    $this->actingAs($warga);
    $warga->update(['status' => 'inactive']);

    $this->get(route('portal.home'))->assertRedirect(route('portal.login'));
    expect(auth()->check())->toBeFalse();
});

// ── A5: ganti sandi biasa wajib verifikasi sandi lama ────────────────
it('menolak ganti sandi tanpa sandi lama yang benar (login pertama selesai)', function () {
    $warga = makeWarga(['must_change_password' => false]);

    $this->actingAs($warga)
        ->from(route('portal.password.edit'))
        ->post(route('portal.password.update'), [
            'current_password' => 'salah-total',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('rahasia-warga', $warga->refresh()->password))->toBeTrue();
});

it('mengizinkan ganti sandi dengan sandi lama benar', function () {
    $warga = makeWarga(['must_change_password' => false]);

    $this->actingAs($warga)
        ->post(route('portal.password.update'), [
            'current_password' => 'rahasia-warga',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])
        ->assertRedirect(route('portal.home'));

    expect(Hash::check('sandi-baru-123', $warga->refresh()->password))->toBeTrue();
});

it('paksa-ganti login pertama tidak butuh sandi lama (sudah autentik via OTP)', function () {
    $warga = makeWarga(['must_change_password' => true]);

    $this->actingAs($warga)
        ->post(route('portal.password.update'), [
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])
        ->assertRedirect(route('portal.home'));

    $warga->refresh();
    expect($warga->must_change_password)->toBeFalse()
        ->and(Hash::check('sandi-baru-123', $warga->password))->toBeTrue();
});

// ── A6: kebijakan sandi minimal (huruf + angka) ──────────────────────
it('menolak sandi baru yang terlalu lemah', function () {
    $warga = makeWarga(['must_change_password' => true]);

    $this->actingAs($warga)
        ->post(route('portal.password.update'), [
            'password' => 'aaaaaaaa', // tanpa angka
            'password_confirmation' => 'aaaaaaaa',
        ])
        ->assertSessionHasErrors('password');
});
