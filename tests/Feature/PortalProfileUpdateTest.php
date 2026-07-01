<?php

use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function profileUpdateWarga(array $overrides = []): User
{
    return User::factory()->warga()->create(array_merge([
        'desa_id' => Desa::factory()->create()->id,
    ], $overrides));
}

it('warga memperbarui email & No. HP (HP dinormalkan ke 62)', function () {
    $warga = profileUpdateWarga();

    $this->actingAs($warga)
        ->post(route('portal.profile.contact'), [
            'email' => 'Warga@Contoh.COM',
            'phone' => '081234567890',
        ])
        ->assertRedirect(route('portal.profile.edit'));

    $warga->refresh();
    expect($warga->email)->toBe('warga@contoh.com')
        ->and($warga->phone)->toStartWith('62');
});

it('email harus valid saat memperbarui kontak', function () {
    $warga = profileUpdateWarga();

    $this->actingAs($warga)
        ->post(route('portal.profile.contact'), ['email' => 'bukan-email'])
        ->assertSessionHasErrors(['email']);
});

it('warga mengganti sandi dengan sandi lama yang benar', function () {
    $warga = profileUpdateWarga(['password' => Hash::make('sandilama')]);

    $this->actingAs($warga)
        ->post(route('portal.profile.password'), [
            'current_password' => 'sandilama',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])
        ->assertRedirect(route('portal.profile.edit'));

    expect(Hash::check('sandibaru123', $warga->refresh()->password))->toBeTrue();
});

it('gagal ganti sandi bila sandi lama salah', function () {
    $warga = profileUpdateWarga(['password' => Hash::make('sandilama')]);

    $this->actingAs($warga)
        ->post(route('portal.profile.password'), [
            'current_password' => 'salah',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])
        ->assertSessionHasErrors(['current_password']);

    expect(Hash::check('sandilama', $warga->refresh()->password))->toBeTrue();
});

it('menampilkan data kependudukan read-only dengan catatan hubungi admin', function () {
    $warga = profileUpdateWarga(['name' => 'Budi Warga']);

    $this->actingAs($warga)
        ->get(route('portal.profile.edit'))
        ->assertOk()
        ->assertSee('Data Kependudukan')
        ->assertSee('Hanya bisa diubah oleh admin')
        ->assertSee('Budi Warga');
});
