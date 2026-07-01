<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('admin desa membuat warga → data lengkap, tanpa OTP otomatis', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($desa, '3201010101010001'))
        ->call('create')
        ->assertHasNoFormErrors();

    $warga = User::where('nik', '3201010101010001')->first();

    expect($warga)->not->toBeNull()
        ->and($warga->role)->toBe('warga')
        ->and($warga->desa_id)->toBe($desa->id)
        ->and($warga->must_change_password)->toBeTrue()
        // OTP tidak digenerate otomatis saat create — diterbitkan nanti via "Reset OTP".
        ->and($warga->initial_otp)->toBeNull();
});

it('membuat warga wajib mengisi data demografi (kecuali email & no. HP)', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    // Tanpa tanggal_lahir, agama, pekerjaan, dll → harus gagal validasi.
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Tanpa Data', 'nik' => '3201010101019999'])
        ->call('create')
        ->assertHasFormErrors(['tanggal_lahir', 'jenis_kelamin', 'agama_id', 'pekerjaan_id', 'desa_unit_id']);
});

it('nomor HP dinormalkan ke format 62 saat create', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($desa, '3201010101013333', ['phone' => '0812-3456-7890']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('nik', '3201010101013333')->value('phone'))->toBe('6281234567890');
});

it('NIK harus 16 digit', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'nik' => '123'])
        ->call('create')
        ->assertHasFormErrors(['nik']);
});

it('beberapa warga tanpa email/HP bisa dibuat (keduanya opsional)', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    foreach (['3201010101010001', '3201010101010002'] as $nik) {
        Livewire::test(CreateUser::class)
            ->fillForm(wargaFormData($desa, $nik))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    expect(User::whereIn('nik', ['3201010101010001', '3201010101010002'])->count())->toBe(2);
});

it('warga login dengan NIK + OTP lalu dipaksa ganti sandi', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create([
        'desa_id' => $desa->id,
        'nik' => '3201010101010009',
    ]);
    $otp = $warga->issueOtp();

    // Login NIK + OTP berhasil.
    $this->post(route('login'), ['login' => '3201010101010009', 'password' => $otp])
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
        'nik' => '3201010101010010',
    ]);
    $otp = $warga->issueOtp();

    // Walau OTP diterbitkan jauh di masa lalu, tetap berlaku.
    $this->travel(60)->days();

    $this->post(route('login'), ['login' => '3201010101010010', 'password' => $otp])
        ->assertRedirect(route('portal.home'));
});

it('admin login di halaman gabungan diarahkan ke panel admin', function () {
    $admin = User::factory()->desaAdmin()->create([
        'desa_id' => Desa::factory()->create()->id,
        'username' => 'adminx',
    ]);

    $this->post(route('login'), ['login' => 'adminx', 'password' => 'password'])
        ->assertRedirect('/admin');

    expect(auth()->id())->toBe($admin->id);
});

it('rute self-register sudah dihapus', function () {
    $this->get('/portal/register')->assertNotFound();
});
