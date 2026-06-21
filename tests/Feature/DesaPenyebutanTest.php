<?php

use App\Filament\Pages\PengaturanDesa;
use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('nama lengkap menggabungkan jenis administratif dengan nama', function () {
    $desa = Desa::factory()->create(['jenis' => 'Kelurahan', 'nama' => 'Melati']);

    expect($desa->nama_lengkap)->toBe('Kelurahan Melati');
});

it('sebutan sub-unit punya fallback saat belum diatur', function () {
    $desa = Desa::factory()->create(['wilayah_label' => null]);

    expect($desa->subUnitLabel())->toBe('Sub-Unit Wilayah');

    $desa->update(['wilayah_label' => 'Jorong']);

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

it('admin desa dapat mengatur sebutan sub-unit & kontak desanya', function () {
    $desa = Desa::factory()->create(['wilayah_label' => null]);
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);

    $this->actingAs($admin);

    Livewire::test(PengaturanDesa::class)
        ->fillForm(['wilayah_label' => 'Dusun', 'kontak' => '081299998888'])
        ->call('save')
        ->assertHasNoFormErrors();

    $desa->refresh();
    expect($desa->wilayah_label)->toBe('Dusun')
        ->and($desa->kontak)->toBe('081299998888');
});
