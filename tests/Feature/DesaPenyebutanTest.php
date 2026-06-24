<?php

use App\Filament\Pages\PengaturanDesa;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('nama lengkap menggabungkan jenis administratif dengan nama', function () {
    $desa = Desa::factory()->jenis('Kelurahan')->create(['nama' => 'Melati']);

    expect($desa->nama_lengkap)->toBe('Kelurahan Melati');
});

it('sebutan sub-unit punya fallback saat belum diatur', function () {
    $desa = Desa::factory()->subUnit(null)->create();

    expect($desa->subUnitLabel())->toBe('Sub-Unit Wilayah');

    $jorong = JenisSubUnit::firstOrCreate(['nama' => 'Jorong']);
    $desa->update(['jenis_sub_unit_id' => $jorong->id]);

    expect($desa->fresh()->subUnitLabel())->toBe('Jorong');
});

it('hanya admin desa yang bisa mengakses Pengaturan Desa', function () {
    $this->actingAs(User::factory()->desaAdmin()->create());
    expect(PengaturanDesa::canAccess())->toBeTrue();

    $this->actingAs(User::factory()->warga()->create());
    expect(PengaturanDesa::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->superAdmin()->create());
    expect(PengaturanDesa::canAccess())->toBeFalse();
});

it('pengaturan desa menampilkan identitas resmi (read-only) & bagian peta', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);

    $this->actingAs($admin);

    Livewire::test(PengaturanDesa::class)
        ->assertSee('Identitas Desa')
        ->assertSee($desa->nama_lengkap)
        ->assertSee($desa->wilayah_kode)
        ->assertSee('Peta Desa');
});

it('endpoint peta batas mengembalikan GeoJSON desa milik admin', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);

    $this->actingAs($admin)
        ->getJson(route('admin.desa.boundary'))
        ->assertOk()
        ->assertJsonStructure(['nama', 'center', 'geometry'])
        ->assertJsonPath('nama', $desa->nama_lengkap);
});

it('endpoint peta batas ditolak untuk non-admin desa', function () {
    $super = User::factory()->superAdmin()->create();

    $this->actingAs($super)
        ->get(route('admin.desa.boundary'))
        ->assertForbidden();
});

it('admin desa dapat mengatur penyebutan desa & sub-unit', function () {
    $desa = Desa::factory()->subUnit(null)->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $nagari = JenisDesa::firstOrCreate(['nama' => 'Nagari'], ['urutan' => 1]);
    $dusun = JenisSubUnit::firstOrCreate(['nama' => 'Dusun']);

    $this->actingAs($admin);

    Livewire::test(PengaturanDesa::class)
        ->fillForm(['jenis_desa_id' => $nagari->id, 'jenis_sub_unit_id' => $dusun->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $desa->refresh();
    expect($desa->jenisDesa->nama)->toBe('Nagari')
        ->and($desa->jenisSubUnit->nama)->toBe('Dusun');
});
