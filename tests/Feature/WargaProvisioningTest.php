<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin nagari membuat warga → NIK + OTP, wajib ganti sandi', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Warga Uji',
            'username' => '3201010101010001',
            'role' => 'warga',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $warga = User::where('username', '3201010101010001')->first();

    expect($warga)->not->toBeNull()
        ->and($warga->role)->toBe('warga')
        ->and($warga->nagari_id)->toBe($nagari->id)
        ->and($warga->must_change_password)->toBeTrue()
        ->and($warga->initial_otp)->not->toBeNull()
        ->and(Hash::check($warga->initial_otp, $warga->password))->toBeTrue();
});

it('NIK harus 16 digit', function () {
    $nagari = Nagari::factory()->create();
    $this->actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'username' => '123', 'role' => 'warga'])
        ->call('create')
        ->assertHasFormErrors(['username']);
});

it('beberapa warga tanpa email bisa dibuat (email opsional)', function () {
    $nagari = Nagari::factory()->create();
    $this->actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]));

    foreach (['3201010101010001', '3201010101010002'] as $nik) {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Warga '.$nik, 'username' => $nik, 'role' => 'warga'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    expect(User::whereIn('username', ['3201010101010001', '3201010101010002'])->count())->toBe(2);
});

it('warga login dengan NIK + OTP lalu dipaksa ganti sandi', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create([
        'nagari_id' => $nagari->id,
        'username' => '3201010101010009',
    ]);
    $otp = $warga->issueOtp();

    // Login NIK + OTP berhasil.
    $this->post(route('portal.login'), ['login' => '3201010101010009', 'password' => $otp])
        ->assertRedirect(route('portal.home'));

    // Akses portal dialihkan ke ganti sandi.
    $this->get(route('portal.home'))->assertRedirect(route('portal.password.edit'));

    // Ganti sandi.
    $this->post(route('portal.password.update'), [
        'password' => 'sandibaru123',
        'password_confirmation' => 'sandibaru123',
    ])->assertRedirect(route('portal.home'));

    $warga->refresh();
    expect($warga->must_change_password)->toBeFalse()
        ->and($warga->initial_otp)->toBeNull()
        ->and($warga->otp_expires_at)->toBeNull();

    // Portal kini bisa diakses.
    $this->get(route('portal.home'))->assertSuccessful();
});

it('OTP awal punya masa berlaku saat diterbitkan', function () {
    $warga = User::factory()->warga()->create(['nagari_id' => Nagari::factory()->create()->id]);
    $warga->issueOtp();

    expect($warga->otp_expires_at)->not->toBeNull()
        ->and($warga->otp_expires_at->isFuture())->toBeTrue();
});

it('login ditolak bila OTP awal sudah kedaluwarsa', function () {
    $nagari = Nagari::factory()->create();
    $warga = User::factory()->warga()->create([
        'nagari_id' => $nagari->id,
        'username' => '3201010101010010',
    ]);
    $otp = $warga->issueOtp();

    // Mundurkan masa berlaku ke masa lalu.
    $warga->forceFill(['otp_expires_at' => now()->subDay()])->save();

    $this->post(route('portal.login'), ['login' => '3201010101010010', 'password' => $otp])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('admin tidak bisa login ke portal warga', function () {
    $admin = User::factory()->nagariAdmin()->create([
        'nagari_id' => Nagari::factory()->create()->id,
        'username' => 'adminx',
    ]);

    $this->post(route('portal.login'), ['login' => 'adminx', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('rute self-register sudah dihapus', function () {
    $this->get('/portal/register')->assertNotFound();
});
