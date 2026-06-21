<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin desa membuat warga → NIK + OTP, wajib ganti sandi', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
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
        ->and($warga->desa_id)->toBe($desa->id)
        ->and($warga->must_change_password)->toBeTrue()
        ->and($warga->initial_otp)->not->toBeNull()
        ->and(Hash::check($warga->initial_otp, $warga->password))->toBeTrue();
});

it('NIK harus 16 digit', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'username' => '123', 'role' => 'warga'])
        ->call('create')
        ->assertHasFormErrors(['username']);
});

it('beberapa warga tanpa email bisa dibuat (email opsional)', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    foreach (['3201010101010001', '3201010101010002'] as $nik) {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Warga '.$nik, 'username' => $nik, 'role' => 'warga'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    expect(User::whereIn('username', ['3201010101010001', '3201010101010002'])->count())->toBe(2);
});

it('warga login dengan NIK + OTP lalu dipaksa ganti sandi', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create([
        'desa_id' => $desa->id,
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
    // OTP awal terhapus otomatis begitu sandi diganti (tanpa kedaluwarsa).
    expect($warga->must_change_password)->toBeFalse()
        ->and($warga->initial_otp)->toBeNull();

    // Portal kini bisa diakses.
    $this->get(route('portal.home'))->assertSuccessful();
});

it('OTP awal bisa diisi manual atau otomatis, dan tanpa kedaluwarsa', function () {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);

    // Manual: kode yang diisi dipakai apa adanya.
    $manual = $warga->issueOtp('789012');
    expect($manual)->toBe('789012')
        ->and($warga->initial_otp)->toBe('789012')
        ->and($warga->must_change_password)->toBeTrue();

    // Otomatis: 6 digit.
    $auto = $warga->issueOtp();
    expect($auto)->toMatch('/^\d{6}$/')
        ->and($warga->initial_otp)->toBe($auto);
});

it('login warga tetap diterima walau OTP lama (tanpa kedaluwarsa)', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create([
        'desa_id' => $desa->id,
        'username' => '3201010101010010',
    ]);
    $otp = $warga->issueOtp();

    // Walau OTP diterbitkan jauh di masa lalu, tetap berlaku.
    $this->travel(60)->days();

    $this->post(route('portal.login'), ['login' => '3201010101010010', 'password' => $otp])
        ->assertRedirect(route('portal.home'));
});

it('admin tidak bisa login ke portal warga', function () {
    $admin = User::factory()->desaAdmin()->create([
        'desa_id' => Desa::factory()->create()->id,
        'username' => 'adminx',
    ]);

    $this->post(route('portal.login'), ['login' => 'adminx', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('rute self-register sudah dihapus', function () {
    $this->get('/portal/register')->assertNotFound();
});
