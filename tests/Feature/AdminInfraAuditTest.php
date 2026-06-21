<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ── I2: desa_admin tak bisa eskalasi peran ─────────────────────────
it('desa_admin tak bisa membuat akun admin (role dipaksa warga)', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Penyusup',
            'username' => '3201000000000777',
            'email' => 'penyusup@x.test',
            'role' => 'super_admin',
            'status' => 'active',
        ])
        ->call('create');

    // Tak ada admin baru; bila terbuat, dipaksa jadi warga.
    expect(User::where('role', 'super_admin')->count())->toBe(0)
        ->and(User::where('username', '3201000000000777')->value('role'))
        ->toBeIn([null, 'warga']);
});

it('desa_admin tak bisa menaikkan peran warga jadi admin', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'email' => 'w@x.test']);
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $warga->getRouteKey()])
        ->fillForm(['role' => 'desa_admin', 'email' => 'w@x.test'])
        ->call('save');

    expect($warga->refresh()->role)->toBe('warga');
});

// ── I1: bulk delete tak boleh menghapus akun sendiri ─────────────────
it('super_admin tak bisa bulk-delete akun sendiri', function () {
    $desa = Desa::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);

    $this->actingAs($super);

    Livewire::test(ListUsers::class)
        ->callTableBulkAction('delete', [$super, $warga]);

    // Aksi dibatalkan → tak ada yang terhapus.
    $this->assertNotSoftDeleted($super);
    $this->assertNotSoftDeleted($warga);
});
