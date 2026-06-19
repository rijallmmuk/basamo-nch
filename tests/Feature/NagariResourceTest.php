<?php

use App\Filament\Resources\Nagaris\Pages\CreateNagari;
use App\Filament\Resources\Nagaris\Pages\ListNagaris;
use App\Models\Nagari;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'nagari_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('super_admin dapat membuat nagari dan kode dinormalkan huruf besar', function () {
    actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CreateNagari::class)
        ->fillForm([
            'nama' => 'Nagari Baru',
            'kode' => 'nch-099',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Nagari::where('kode', 'NCH-099')->exists())->toBeTrue();
});

it('nagari yang masih punya warga tidak bisa dihapus', function () {
    actingAs(User::factory()->superAdmin()->create());
    $nagari = Nagari::factory()->create();
    User::factory()->warga()->create(['nagari_id' => $nagari->id]);

    Livewire::test(ListNagaris::class)
        ->callTableAction('delete', $nagari);

    expect($nagari->fresh()->trashed())->toBeFalse();
});

it('nagari kosong bisa diarsipkan (soft delete)', function () {
    actingAs(User::factory()->superAdmin()->create());
    $nagari = Nagari::factory()->create();

    Livewire::test(ListNagaris::class)
        ->callTableAction('delete', $nagari);

    expect($nagari->fresh()->trashed())->toBeTrue();
});

it('nagari_admin tidak boleh mengelola nagari', function () {
    $admin = User::factory()->nagariAdmin()->create();

    expect(Gate::forUser($admin)->check('viewAny', Nagari::class))->toBeFalse();
});
