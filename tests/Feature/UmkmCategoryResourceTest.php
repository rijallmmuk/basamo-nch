<?php

use App\Filament\Resources\UmkmCategories\Pages\ManageUmkmCategories;
use App\Models\UmkmCategory;
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
            'sort_order' => 9,
        ]);

    $cat = UmkmCategory::where('nama', 'Otomotif')->first();
    expect($cat)->not->toBeNull()
        ->and($cat->slug)->toBe('otomotif');
});

it('desa_admin tidak dapat mengelola kategori UMKM (global)', function () {
    $admin = User::factory()->desaAdmin()->create();

    expect(Gate::forUser($admin)->check('viewAny', UmkmCategory::class))->toBeFalse();
});
