<?php

use App\Filament\Resources\DesaUnits\Pages\CreateDesaUnit;
use App\Filament\Resources\DesaUnits\Pages\ListDesaUnits;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('desa_admin membuat wilayah ter-scope ke desanya', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateDesaUnit::class)
        ->fillForm(['nama' => 'Jorong Koto Tuo'])
        ->call('create')
        ->assertHasNoFormErrors();

    $w = DesaUnit::first();
    expect($w->nama)->toBe('Jorong Koto Tuo')
        ->and($w->desa_id)->toBe($desa->id);
});

it('nama wilayah unik per desa, boleh sama antar desa', function () {
    $a = Desa::factory()->create();
    $b = Desa::factory()->create();
    DesaUnit::create(['desa_id' => $a->id, 'nama' => 'Dusun Satu']);

    $this->actingAs(User::factory()->superAdmin()->create());

    // Nama sama di desa berbeda → boleh.
    Livewire::test(CreateDesaUnit::class)
        ->fillForm(['desa_id' => $b->id, 'nama' => 'Dusun Satu'])
        ->call('create')
        ->assertHasNoFormErrors();

    // Duplikat di desa yang sama → gagal.
    Livewire::test(CreateDesaUnit::class)
        ->fillForm(['desa_id' => $a->id, 'nama' => 'Dusun Satu'])
        ->call('create')
        ->assertHasFormErrors(['nama']);
});

it('desa_admin hanya melihat wilayah desanya', function () {
    $a = Desa::factory()->create();
    $b = Desa::factory()->create();
    DesaUnit::create(['desa_id' => $a->id, 'nama' => 'A1']);
    DesaUnit::create(['desa_id' => $b->id, 'nama' => 'B1']);

    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $a->id]));

    Livewire::test(ListDesaUnits::class)
        ->assertCanSeeTableRecords(DesaUnit::where('desa_id', $a->id)->get())
        ->assertCanNotSeeTableRecords(DesaUnit::where('desa_id', $b->id)->get());
});

it('warga bisa diberi wilayah desanya, wilayah desa lain ditolak', function () {
    $a = Desa::factory()->create();
    $b = Desa::factory()->create();
    $wA = DesaUnit::create(['desa_id' => $a->id, 'nama' => 'A-Jorong']);
    $wB = DesaUnit::create(['desa_id' => $b->id, 'nama' => 'B-Jorong']);

    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $a->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'W', 'username' => '3201010101019001', 'role' => 'warga', 'desa_unit_id' => $wA->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('username', '3201010101019001')->first()->desa_unit_id)->toBe($wA->id);

    // Wilayah milik desa lain → ditolak.
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'W2', 'username' => '3201010101019002', 'role' => 'warga', 'desa_unit_id' => $wB->id])
        ->call('create')
        ->assertHasFormErrors(['desa_unit_id']);
});
