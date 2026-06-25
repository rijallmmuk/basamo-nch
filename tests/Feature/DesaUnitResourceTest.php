<?php

use App\Filament\Resources\DesaUnits\Pages\CreateDesaUnit;
use App\Filament\Resources\DesaUnits\Pages\ListDesaUnits;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\User;
use App\Support\DesaContext;
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

    // Super admin mengelola per-desa lewat konteks (aksi "Kelola Wilayah").
    // Nama sama di desa berbeda → boleh.
    DesaContext::set($b->id);
    Livewire::test(CreateDesaUnit::class)
        ->fillForm(['nama' => 'Dusun Satu'])
        ->call('create')
        ->assertHasNoFormErrors();

    // Duplikat di desa yang sama → gagal.
    DesaContext::set($a->id);
    Livewire::test(CreateDesaUnit::class)
        ->fillForm(['nama' => 'Dusun Satu'])
        ->call('create')
        ->assertHasFormErrors(['nama']);
});

it('nama wilayah yang sudah dihapus boleh dipakai ulang', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong Lama']);
    $unit->delete(); // soft delete

    // Nama bekas yang sudah dihapus → boleh dibuat lagi (unique menyertakan deleted_at).
    Livewire::test(CreateDesaUnit::class)
        ->fillForm(['nama' => 'Jorong Lama'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DesaUnit::where('desa_id', $desa->id)->where('nama', 'Jorong Lama')->count())->toBe(1);
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

it('wilayah yang masih dihuni warga tidak bisa dihapus', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));
    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong Huni']);
    User::factory()->warga()->create(['desa_id' => $desa->id, 'desa_unit_id' => $unit->id]);

    Livewire::test(ListDesaUnits::class)
        ->callTableAction('delete', $unit);

    expect($unit->fresh()->trashed())->toBeFalse(); // guard meng-halt hapus
});

it('wilayah kosong bisa dihapus (soft delete)', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));
    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong Kosong']);

    Livewire::test(ListDesaUnits::class)
        ->callTableAction('delete', $unit);

    expect($unit->fresh()->trashed())->toBeTrue();
});

it('kolom Warga hanya menghitung warga, bukan akun admin', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));
    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong Hitung']);

    User::factory()->warga()->create(['desa_id' => $desa->id, 'desa_unit_id' => $unit->id]);
    User::factory()->warga()->create(['desa_id' => $desa->id, 'desa_unit_id' => $unit->id]);
    // Akun admin yang kebetulan beralamat di sub-unit ini tak boleh ikut terhitung.
    User::factory()->desaAdmin()->create(['desa_id' => $desa->id, 'desa_unit_id' => $unit->id]);

    expect(DesaUnit::withCount('warga')->find($unit->id)->warga_count)->toBe(2);
});

it('warga bisa diberi wilayah desanya, wilayah desa lain ditolak', function () {
    $a = Desa::factory()->create();
    $b = Desa::factory()->create();
    $wA = DesaUnit::create(['desa_id' => $a->id, 'nama' => 'A-Jorong']);
    $wB = DesaUnit::create(['desa_id' => $b->id, 'nama' => 'B-Jorong']);

    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $a->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($a, '3201010101019001', ['desa_unit_id' => $wA->id]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('nik', '3201010101019001')->first()->desa_unit_id)->toBe($wA->id);

    // Wilayah milik desa lain → ditolak.
    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($a, '3201010101019002', ['desa_unit_id' => $wB->id]))
        ->call('create')
        ->assertHasFormErrors(['desa_unit_id']);
});
