<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Wilayahs\Pages\CreateWilayah;
use App\Filament\Resources\Wilayahs\Pages\ListWilayahs;
use App\Models\Nagari;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('nagari_admin membuat wilayah ter-scope ke nagarinya', function () {
    $nagari = Nagari::factory()->create();
    $this->actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]));

    Livewire::test(CreateWilayah::class)
        ->fillForm(['nama' => 'Jorong Koto Tuo'])
        ->call('create')
        ->assertHasNoFormErrors();

    $w = Wilayah::first();
    expect($w->nama)->toBe('Jorong Koto Tuo')
        ->and($w->nagari_id)->toBe($nagari->id);
});

it('nama wilayah unik per nagari, boleh sama antar nagari', function () {
    $a = Nagari::factory()->create();
    $b = Nagari::factory()->create();
    Wilayah::create(['nagari_id' => $a->id, 'nama' => 'Dusun Satu']);

    $this->actingAs(User::factory()->superAdmin()->create());

    // Nama sama di nagari berbeda → boleh.
    Livewire::test(CreateWilayah::class)
        ->fillForm(['nagari_id' => $b->id, 'nama' => 'Dusun Satu'])
        ->call('create')
        ->assertHasNoFormErrors();

    // Duplikat di nagari yang sama → gagal.
    Livewire::test(CreateWilayah::class)
        ->fillForm(['nagari_id' => $a->id, 'nama' => 'Dusun Satu'])
        ->call('create')
        ->assertHasFormErrors(['nama']);
});

it('nagari_admin hanya melihat wilayah nagarinya', function () {
    $a = Nagari::factory()->create();
    $b = Nagari::factory()->create();
    Wilayah::create(['nagari_id' => $a->id, 'nama' => 'A1']);
    Wilayah::create(['nagari_id' => $b->id, 'nama' => 'B1']);

    $this->actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $a->id]));

    Livewire::test(ListWilayahs::class)
        ->assertCanSeeTableRecords(Wilayah::where('nagari_id', $a->id)->get())
        ->assertCanNotSeeTableRecords(Wilayah::where('nagari_id', $b->id)->get());
});

it('warga bisa diberi wilayah nagarinya, wilayah nagari lain ditolak', function () {
    $a = Nagari::factory()->create();
    $b = Nagari::factory()->create();
    $wA = Wilayah::create(['nagari_id' => $a->id, 'nama' => 'A-Jorong']);
    $wB = Wilayah::create(['nagari_id' => $b->id, 'nama' => 'B-Jorong']);

    $this->actingAs(User::factory()->nagariAdmin()->create(['nagari_id' => $a->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'W', 'username' => '3201010101019001', 'role' => 'warga', 'wilayah_id' => $wA->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('username', '3201010101019001')->first()->wilayah_id)->toBe($wA->id);

    // Wilayah milik nagari lain → ditolak.
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'W2', 'username' => '3201010101019002', 'role' => 'warga', 'wilayah_id' => $wB->id])
        ->call('create')
        ->assertHasFormErrors(['wilayah_id']);
});
