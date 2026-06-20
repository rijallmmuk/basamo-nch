<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ── I2: nagari_admin tak bisa eskalasi peran ─────────────────────────
it('nagari_admin tak bisa membuat akun admin (role dipaksa warga)', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
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

it('nagari_admin tak bisa menaikkan peran warga jadi admin', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id, 'email' => 'w@x.test']);
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $warga->getRouteKey()])
        ->fillForm(['role' => 'nagari_admin', 'email' => 'w@x.test'])
        ->call('save');

    expect($warga->refresh()->role)->toBe('warga');
});

// ── I1: bulk delete tak boleh menghapus akun sendiri ─────────────────
it('super_admin tak bisa bulk-delete akun sendiri', function () {
    $nagari = Nagari::factory()->create();
    $super = User::factory()->superAdmin()->create();
    $warga = User::factory()->warga()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($super);

    Livewire::test(ListUsers::class)
        ->callTableBulkAction('delete', [$super, $warga]);

    // Aksi dibatalkan → tak ada yang terhapus.
    $this->assertNotSoftDeleted($super);
    $this->assertNotSoftDeleted($warga);
});
