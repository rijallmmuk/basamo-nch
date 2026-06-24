<?php

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Desas\DesaResource;
use App\Filament\Resources\DesaUnits\DesaUnitResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\UmkmCategories\UmkmCategoryResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sidebar super admin: Desa jadi hero, resource per-desa & Kategori tampil sesuai aturan', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    // Desa = hero, tingkat atas (tanpa grup), tampil.
    expect(DesaResource::getNavigationGroup())->toBeNull()
        ->and(DesaResource::canAccess())->toBeTrue();

    // Resource per-desa disembunyikan dari sidebar global super admin.
    expect(UserResource::shouldRegisterNavigation())->toBeFalse()
        ->and(UmkmProfileResource::shouldRegisterNavigation())->toBeFalse()
        ->and(DesaUnitResource::shouldRegisterNavigation())->toBeFalse();

    // LMS & Kategori UMKM = ranah super admin.
    expect(UmkmCategoryResource::canAccess())->toBeTrue()
        ->and(ModuleResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ModuleResource::getNavigationGroup())->toBe('LMS')
        ->and(ActivityLogResource::getNavigationGroup())->toBe('Sistem');
});

it('sidebar admin desa: Warga jadi hero, tanpa Desa & tanpa Kategori UMKM', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    // Warga hero (tingkat atas), tampil.
    expect(UserResource::getNavigationGroup())->toBeNull()
        ->and(UserResource::shouldRegisterNavigation())->toBeTrue();

    // Desa & Kategori UMKM (global) tidak untuk admin desa.
    expect(DesaResource::canAccess())->toBeFalse()
        ->and(UmkmCategoryResource::canAccess())->toBeFalse();

    // Resource per-desa tampil; LMS disembunyikan (bukan dihapus → tetap dapat diakses).
    expect(UmkmProfileResource::shouldRegisterNavigation())->toBeTrue()
        ->and(DesaUnitResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ModuleResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ModuleResource::canAccess())->toBeTrue();
});
