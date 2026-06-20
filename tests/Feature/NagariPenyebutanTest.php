<?php

use App\Filament\Pages\PengaturanNagari;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('nama lengkap menggabungkan jenis administratif dengan nama', function () {
    $nagari = Nagari::factory()->create(['jenis' => 'Kelurahan', 'nama' => 'Melati']);

    expect($nagari->nama_lengkap)->toBe('Kelurahan Melati');
});

it('sebutan sub-unit punya fallback saat belum diatur', function () {
    $nagari = Nagari::factory()->create(['wilayah_label' => null]);

    expect($nagari->subUnitLabel())->toBe('Sub-Unit Wilayah');

    $nagari->update(['wilayah_label' => 'Jorong']);

    expect($nagari->fresh()->subUnitLabel())->toBe('Jorong');
});

it('hanya admin nagari yang bisa mengakses Pengaturan Nagari', function () {
    $this->actingAs(User::factory()->nagariAdmin()->create());
    expect(PengaturanNagari::canAccess())->toBeTrue();

    $this->actingAs(User::factory()->warga()->create());
    expect(PengaturanNagari::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->superAdmin()->create());
    expect(PengaturanNagari::canAccess())->toBeFalse();
});

it('admin nagari dapat mengatur sebutan sub-unit & kontak nagarinya', function () {
    $nagari = Nagari::factory()->create(['wilayah_label' => null]);
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($admin);

    Livewire::test(PengaturanNagari::class)
        ->fillForm(['wilayah_label' => 'Dusun', 'kontak' => '081299998888'])
        ->call('save')
        ->assertHasNoFormErrors();

    $nagari->refresh();
    expect($nagari->wilayah_label)->toBe('Dusun')
        ->and($nagari->kontak)->toBe('081299998888');
});
