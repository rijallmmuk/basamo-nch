<?php

use App\Filament\Resources\UmkmCategories\Pages\ManageUmkmCategories;
use App\Models\Desa;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('super_admin dapat mengelola kategori UMKM', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ManageUmkmCategories::class)
        ->assertOk()
        ->callAction('create', data: [
            'nama' => 'Otomotif',
            'urutan' => 9,
        ]);

    $cat = UmkmCategory::where('nama', 'Otomotif')->first();
    expect($cat)->not->toBeNull()
        ->and($cat->slug)->toBe('otomotif');
});

it('desa_admin tidak dapat mengelola kategori UMKM (global)', function () {
    $admin = User::factory()->desaAdmin()->create();

    expect(Gate::forUser($admin)->check('viewAny', UmkmCategory::class))->toBeFalse();
});

it('nama kategori harus unik & kategori terpakai tidak bisa dihapus', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    // Nama duplikat ("Kuliner" bawaan ter-seed) ditolak validasi.
    Livewire::test(ManageUmkmCategories::class)
        ->callAction('create', data: ['nama' => 'Kuliner'])
        ->assertHasActionErrors(['nama']);
    expect(UmkmCategory::where('nama', 'Kuliner')->count())->toBe(1);

    // Kategori yang dipakai UMKM tidak boleh dihapus (guard before delete).
    $kuliner = UmkmCategory::where('slug', 'kuliner')->first();
    $desa = Desa::factory()->create();
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);
    $lapak = UmkmProfile::factory()->create(['desa_id' => $desa->id, 'user_id' => $owner->id]);
    UmkmProduct::factory()->create(['umkm_profile_id' => $lapak->id, 'umkm_category_id' => $kuliner->id]);

    Livewire::test(ManageUmkmCategories::class)->callTableAction('delete', $kuliner);
    expect(UmkmCategory::whereKey($kuliner->id)->exists())->toBeTrue();
});
