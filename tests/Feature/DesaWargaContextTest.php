<?php

use App\Filament\Resources\Desas\Pages\ListDesas;
use App\Filament\Resources\Users\UserResource;
use App\Models\Desa;
use App\Models\User;
use App\Support\DesaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('super admin tanpa konteks desa tidak boleh mengakses halaman Warga', function () {
    actingAs(User::factory()->superAdmin()->create());

    expect(UserResource::canAccess())->toBeFalse()
        ->and(auth()->user()->managedDesaId())->toBeNull();
});

it('super admin dengan konteks desa boleh akses Warga & ter-scope ke desa itu', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->superAdmin()->create());

    DesaContext::set($desa->id);

    expect(UserResource::canAccess())->toBeTrue()
        ->and(auth()->user()->managedDesaId())->toBe($desa->id)
        ->and(UserResource::shouldRegisterNavigation())->toBeTrue();
});

it('admin desa: managedDesaId selalu desanya sendiri (tanpa konteks session)', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    expect(auth()->user()->managedDesaId())->toBe($desa->id)
        ->and(UserResource::canAccess())->toBeTrue();
});

it('aksi "Kelola Warga" menyetel konteks desa lalu mengalihkan ke halaman Warga', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ListDesas::class)
        ->callTableAction('kelolaWarga', $desa)
        ->assertRedirect(UserResource::getUrl('index'));

    expect(DesaContext::id())->toBe($desa->id);
});

it('membuka daftar Desa membersihkan konteks kelola-warga (cegah stale)', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->superAdmin()->create());
    DesaContext::set($desa->id);

    Livewire::test(ListDesas::class);

    expect(DesaContext::id())->toBeNull();
});
