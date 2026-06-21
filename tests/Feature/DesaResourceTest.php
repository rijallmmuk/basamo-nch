<?php

use App\Filament\Resources\Desas\Pages\CreateDesa;
use App\Filament\Resources\Desas\Pages\ListDesas;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('super_admin dapat membuat desa dan kode dinormalkan huruf besar', function () {
    actingAs(User::factory()->superAdmin()->create());

    $jenis = JenisDesa::firstOrCreate(['nama' => 'Desa']);

    Livewire::test(CreateDesa::class)
        ->fillForm([
            'nama' => 'Desa Baru',
            'jenis_desa_id' => $jenis->id,
            'kode' => 'nch-099',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Desa::where('kode', 'NCH-099')->exists())->toBeTrue();
});

it('desa yang masih punya warga tidak bisa dihapus', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    User::factory()->warga()->create(['desa_id' => $desa->id]);

    Livewire::test(ListDesas::class)
        ->callTableAction('delete', $desa);

    expect($desa->fresh()->trashed())->toBeFalse();
});

it('desa kosong bisa diarsipkan (soft delete)', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();

    Livewire::test(ListDesas::class)
        ->callTableAction('delete', $desa);

    expect($desa->fresh()->trashed())->toBeTrue();
});

it('desa_admin tidak boleh mengelola desa', function () {
    $admin = User::factory()->desaAdmin()->create();

    expect(Gate::forUser($admin)->check('viewAny', Desa::class))->toBeFalse();
});
