<?php

use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Pekerjaan;
use App\Models\StatusPerkawinan;
use App\Services\WargaProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('membuat warga ter-scope ke desa + penduduk + OTP manual jadi sandi awal', function () {
    $desa = Desa::factory()->create();
    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong A']);
    $agama = Agama::firstOrCreate(['nama' => 'Islam'], ['urutan' => 1]);
    $status = StatusPerkawinan::firstOrCreate(['nama' => 'Belum Kawin'], ['urutan' => 1]);
    $kerja = Pekerjaan::firstOrCreate(['kode' => '01'], ['nama' => 'Petani', 'urutan' => 1]);

    $user = app(WargaProvisioningService::class)->create([
        'name' => 'Warga Detail',
        'nik' => '3201019090900001',
        'email' => null,
        'phone' => null,
        'desa_unit_id' => $unit->id,
        'status' => 'active',
        'initial_otp' => '135790',
        'tempat_lahir' => 'Padang',
        'tanggal_lahir' => '1990-05-17',
        'jenis_kelamin' => 'L',
        'agama_id' => $agama->id,
        'status_perkawinan_id' => $status->id,
        'pekerjaan_id' => $kerja->id,
    ], $desa->id);

    expect($user->desa_id)->toBe($desa->id)
        ->and($user->role)->toBe('warga')
        ->and($user->desa_unit_id)->toBe($unit->id)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->initial_otp)->toBe('135790')
        ->and($user->refresh()->penduduk?->tempat_lahir)->toBe('Padang')
        ->and($user->penduduk?->jenis_kelamin?->value)->toBe('L');
});

it('memperbarui akun + identitas penduduk', function () {
    $desa = Desa::factory()->create();
    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong A']);
    $agama = Agama::firstOrCreate(['nama' => 'Islam'], ['urutan' => 1]);
    $kerja = Pekerjaan::firstOrCreate(['kode' => '01'], ['nama' => 'Petani', 'urutan' => 1]);
    $status = StatusPerkawinan::firstOrCreate(['nama' => 'Belum Kawin'], ['urutan' => 1]);

    $service = app(WargaProvisioningService::class);
    $user = $service->create([
        'name' => 'Nama Lama', 'nik' => '3201019090900002', 'desa_unit_id' => $unit->id,
        'status' => 'active', 'tempat_lahir' => 'Solok', 'tanggal_lahir' => '1991-01-01',
        'jenis_kelamin' => 'P', 'agama_id' => $agama->id, 'status_perkawinan_id' => $status->id,
        'pekerjaan_id' => $kerja->id,
    ], $desa->id);

    $service->update($user, ['name' => 'Nama Baru', 'tempat_lahir' => 'Bukittinggi']);

    expect($user->refresh()->name)->toBe('Nama Baru')
        ->and($user->penduduk->tempat_lahir)->toBe('Bukittinggi');
});
