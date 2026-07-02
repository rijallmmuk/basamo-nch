<?php

use App\Filament\Resources\DataMaster\AgamaResource;
use App\Filament\Resources\DataMaster\JenisDesaResource;
use App\Filament\Resources\DataMaster\JenisSubUnitResource;
use App\Filament\Resources\DataMaster\Pages\ManageAgama;
use App\Filament\Resources\DataMaster\Pages\ManageJenisDesa;
use App\Filament\Resources\DataMaster\Pages\ManageJenisSubUnit;
use App\Filament\Resources\DataMaster\Pages\ManagePekerjaan;
use App\Filament\Resources\DataMaster\Pages\ManageStatusPerkawinan;
use App\Filament\Resources\DataMaster\PekerjaanResource;
use App\Filament\Resources\DataMaster\StatusPerkawinanResource;
use App\Models\Agama;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

dataset('halamanDataMaster', [
    'agama' => [ManageAgama::class, AgamaResource::class],
    'status perkawinan' => [ManageStatusPerkawinan::class, StatusPerkawinanResource::class],
    'pekerjaan' => [ManagePekerjaan::class, PekerjaanResource::class],
    'penyebutan desa' => [ManageJenisDesa::class, JenisDesaResource::class],
    'sebutan sub-unit' => [ManageJenisSubUnit::class, JenisSubUnitResource::class],
]);

it('super admin bisa membuka tiap halaman data master', function (string $page, string $resource) {
    actingAs(User::factory()->superAdmin()->create());

    Livewire::test($page)->assertSuccessful();
})->with('halamanDataMaster');

it('admin desa ditolak dari tiap halaman data master', function (string $page, string $resource) {
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]));

    $this->get($resource::getUrl())->assertForbidden();
})->with('halamanDataMaster');

it('super admin bisa menambah & mengubah entri data master; urutan otomatis di akhir', function () {
    actingAs(User::factory()->superAdmin()->create());
    $maksSebelum = (int) Agama::max('urutan');

    Livewire::test(ManageAgama::class)
        ->callAction('create', ['nama' => 'Aliran Uji', 'aktif' => true])
        ->assertHasNoActionErrors();

    $agama = Agama::where('nama', 'Aliran Uji')->firstOrFail();
    expect($agama->urutan)->toBe($maksSebelum + 1);

    Livewire::test(ManageAgama::class)
        ->callTableAction('edit', $agama, ['nama' => 'Aliran Uji Ubah', 'aktif' => false])
        ->assertHasNoTableActionErrors();

    expect($agama->refresh())
        ->nama->toBe('Aliran Uji Ubah')
        ->aktif->toBeFalse();
});

it('nama data master harus unik', function () {
    actingAs(User::factory()->superAdmin()->create());
    Agama::create(['nama' => 'Sudah Ada', 'urutan' => 90, 'aktif' => true]);

    Livewire::test(ManageAgama::class)
        ->callAction('create', ['nama' => 'Sudah Ada', 'aktif' => true])
        ->assertHasActionErrors(['nama']);
});

it('pekerjaan baru mendapat kode Dukcapil lanjutan otomatis (kode tak lagi di form)', function () {
    actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ManagePekerjaan::class)
        ->callAction('create', ['nama' => 'Pekerjaan Uji', 'aktif' => true])
        ->assertHasNoActionErrors();

    $pekerjaan = Pekerjaan::where('nama', 'Pekerjaan Uji')->firstOrFail();

    expect($pekerjaan->kode)->not->toBeEmpty()
        ->and(Pekerjaan::where('kode', $pekerjaan->kode)->count())->toBe(1);
});

it('entri yang masih dipakai warga tidak bisa dihapus', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    $agama = Agama::create(['nama' => 'Dipakai Warga', 'urutan' => 92, 'aktif' => true]);
    Penduduk::create([
        'nik' => '3201000000009901', 'nama' => 'Warga Uji',
        'desa_id' => $desa->id, 'agama_id' => $agama->id,
    ]);

    Livewire::test(ManageAgama::class)
        ->callTableAction('delete', $agama);

    expect(Agama::find($agama->id))->not->toBeNull();
});

it('penyebutan desa yang dipakai desa tidak bisa dihapus; yang tak terpakai bisa', function () {
    actingAs(User::factory()->superAdmin()->create());
    $dipakai = JenisDesa::create(['nama' => 'Sebutan Dipakai', 'urutan' => 93, 'aktif' => true]);
    $bebas = JenisDesa::create(['nama' => 'Sebutan Bebas', 'urutan' => 94, 'aktif' => true]);
    Desa::factory()->create(['jenis_desa_id' => $dipakai->id]);

    Livewire::test(ManageJenisDesa::class)
        ->callTableAction('delete', $dipakai);

    Livewire::test(ManageJenisDesa::class)
        ->callTableAction('delete', $bebas);

    expect(JenisDesa::find($dipakai->id))->not->toBeNull()
        ->and(JenisDesa::find($bebas->id))->toBeNull();
});

it('opsi lookup menyaring nonaktif tapi tetap menyertakan nilai terpilih', function () {
    $aktif = Agama::create(['nama' => 'Opsi Aktif', 'urutan' => 95, 'aktif' => true]);
    $nonaktif = Agama::create(['nama' => 'Opsi Nonaktif', 'urutan' => 96, 'aktif' => false]);

    expect(Agama::options())->toHaveKey($aktif->id)
        ->not->toHaveKey($nonaktif->id)
        ->and(Agama::options($nonaktif->id))->toHaveKey($nonaktif->id);
});
