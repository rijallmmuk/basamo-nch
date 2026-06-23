<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Desa;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Resource Warga = khusus warga; akun admin dikelola lewat alur lain (form Desa).

it('akun yang dibuat di resource warga selalu berperan warga (tak ada eskalasi)', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($desa, '3201000000000777'))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('nik', '3201000000000777')->value('role'))->toBe('warga');
});

it('akun admin tak bisa diedit lewat resource warga (route binding ter-scope warga)', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $this->actingAs(User::factory()->superAdmin()->create());

    // Route binding di-scope ke role=warga → record admin tak ditemukan.
    expect(fn () => Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()]))
        ->toThrow(ModelNotFoundException::class);
});
