<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Imports\WargaImport;
use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\JenisSubUnit;
use App\Models\Pekerjaan;
use App\Models\StatusPerkawinan;
use App\Models\User;
use App\Services\WargaImportService;
use App\Services\WargaTemplateBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function wargaRow(array $overrides = []): array
{
    return array_merge([
        'nama' => 'Budi Santoso',
        'nik' => '3201010101010001',
        'jenis_kelamin' => 'Laki-laki',
        'tempat_lahir' => 'Padang',
        'tanggal_lahir' => '1990-05-17',
        'agama' => 'Islam',
        'status_perkawinan' => 'Belum Kawin',
        'pekerjaan' => 'Petani/Pekebun',
        'wilayah' => 'Jorong A',
        'email' => '',
        'no_hp' => '',
        'status' => '',
    ], $overrides);
}

beforeEach(function () {
    $this->desa = Desa::factory()->create(['nama' => 'Sungai Lansek']);
    $this->unit = DesaUnit::create(['desa_id' => $this->desa->id, 'nama' => 'Jorong A']);
    Agama::firstOrCreate(['nama' => 'Islam'], ['urutan' => 1]);
    StatusPerkawinan::firstOrCreate(['nama' => 'Belum Kawin'], ['urutan' => 1]);
    Pekerjaan::firstOrCreate(['kode' => '01'], ['nama' => 'Petani/Pekebun', 'urutan' => 1]);

    $this->admin = User::factory()->desaAdmin()->create(['desa_id' => $this->desa->id]);
    $this->service = app(WargaImportService::class);
    $this->seen = [];
});

it('membuat warga + penduduk dari baris valid, ter-scope ke desa admin', function () {
    $user = $this->service->createFromRow(wargaRow(), $this->admin, $this->seen);

    expect($user->role)->toBe('warga')
        ->and($user->desa_id)->toBe($this->desa->id)
        ->and($user->desa_unit_id)->toBe($this->unit->id)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->initial_otp)->toBeNull();

    $penduduk = $user->refresh()->penduduk;
    expect($penduduk)->not->toBeNull()
        ->and($penduduk->nik)->toBe('3201010101010001')
        ->and($penduduk->jenis_kelamin->value)->toBe('L')
        ->and($penduduk->tempat_lahir)->toBe('Padang')
        ->and($penduduk->desa_id)->toBe($this->desa->id);
});

it('mengabaikan kolom desa dari file untuk desa_admin (paksa desanya sendiri)', function () {
    $lain = Desa::factory()->create(['nama' => 'Desa Lain']);

    $user = $this->service->createFromRow(
        wargaRow(['desa' => 'Desa Lain']),
        $this->admin,
        $this->seen,
    );

    expect($user->desa_id)->toBe($this->desa->id)->not->toBe($lain->id);
});

it('menolak NIK bukan 16 digit', function () {
    $this->service->createFromRow(wargaRow(['nik' => '123']), $this->admin, $this->seen);
})->throws(RuntimeException::class, '16 digit');

it('menolak NIK yang sudah terdaftar', function () {
    // Sudah ada di DB sebelum impor (file lain / sesi lain) → seen array baru.
    $this->service->createFromRow(wargaRow(), $this->admin, $this->seen);

    $seenBaru = [];
    $this->service->createFromRow(wargaRow(['nama' => 'Orang Lain']), $this->admin, $seenBaru);
})->throws(RuntimeException::class, 'sudah terdaftar');

it('menolak wilayah di luar desa (cegah lintas-desa)', function () {
    $lain = Desa::factory()->create(['nama' => 'Desa Lain']);
    DesaUnit::create(['desa_id' => $lain->id, 'nama' => 'Jorong Z']);

    $this->service->createFromRow(wargaRow(['wilayah' => 'Jorong Z']), $this->admin, $this->seen);
})->throws(RuntimeException::class, 'terdaftar di desa');

it('menolak nilai enum/lookup yang tak dikenal', function () {
    $this->service->createFromRow(wargaRow(['agama' => 'Jedi']), $this->admin, $this->seen);
})->throws(RuntimeException::class, 'tidak dikenali');

it('menerima tanggal lahir format d/m/Y', function () {
    $user = $this->service->createFromRow(wargaRow(['tanggal_lahir' => '17/05/1990']), $this->admin, $this->seen);

    expect($user->refresh()->penduduk->tanggal_lahir->toDateString())->toBe('1990-05-17');
});

it('menolak tanggal lahir di masa depan', function () {
    $this->service->createFromRow(wargaRow(['tanggal_lahir' => '2090-01-01']), $this->admin, $this->seen);
})->throws(RuntimeException::class, 'masa depan');

it('menolak tanggal lahir format ngawur', function () {
    $this->service->createFromRow(wargaRow(['tanggal_lahir' => '32 something']), $this->admin, $this->seen);
})->throws(RuntimeException::class, 'tidak dikenali');

it('membaca sub-unit dari kolom bernama sebutan desa (mis. "jorong")', function () {
    // Desa memakai sebutan "Jorong" → template memberi judul kolom "Jorong",
    // sehingga key barisnya ter-slug jadi "jorong".
    $jenis = JenisSubUnit::firstOrCreate(['nama' => 'Jorong'], ['urutan' => 1]);
    $this->desa->update(['jenis_sub_unit_id' => $jenis->id]);

    $row = wargaRow(['wilayah' => null, 'jorong' => 'Jorong A']);

    $user = $this->service->createFromRow($row, $this->admin->refresh(), $this->seen);

    expect($user->desa_unit_id)->toBe($this->unit->id);
});

it('super_admin memetakan desa dari kolom by-nama', function () {
    $super = User::factory()->superAdmin()->create();

    $user = $this->service->createFromRow(
        wargaRow(['desa' => 'Sungai Lansek']),
        $super,
        $this->seen,
    );

    expect($user->desa_id)->toBe($this->desa->id);
});

it('WargaImport mengumpulkan baris berhasil & gagal dengan nomor baris', function () {
    $import = new WargaImport($this->admin, $this->service);

    $import->collection(collect([
        collect(wargaRow()),                                   // baris 2: valid
        collect(wargaRow(['nik' => '999'])),                   // baris 3: NIK invalid
        collect(wargaRow(['nik' => '3201010101019999', 'nama' => 'Siti'])), // baris 4: valid
    ]));

    expect($import->imported)->toBe(2)
        ->and($import->errors)->toHaveCount(1)
        ->and($import->errors[0]['baris'])->toBe(3);
});

it('mendeteksi NIK duplikat di dalam file', function () {
    $import = new WargaImport($this->admin, $this->service);

    $import->collection(collect([
        collect(wargaRow()),                       // baris 2: valid
        collect(wargaRow(['nama' => 'Kembar'])),   // baris 3: NIK sama → gagal
    ]));

    expect($import->imported)->toBe(1)
        ->and($import->errors[0]['pesan'])->toContain('duplikat');
});

it('memblokir unduh template bila wilayah belum siap (sebutan/daftar)', function () {
    // $this->desa punya unit tapi belum punya sebutan sub-unit → belum siap.
    $this->actingAs($this->admin);

    Livewire\Livewire::test(ListUsers::class)
        ->callAction('unduhTemplate')
        ->assertNotified('Lengkapi data Wilayah dulu');
});

it('mengizinkan unduh template bila wilayah lengkap', function () {
    $jenis = JenisSubUnit::firstOrCreate(['nama' => 'Jorong'], ['urutan' => 1]);
    $this->desa->update(['jenis_sub_unit_id' => $jenis->id]);
    $this->actingAs($this->admin->refresh());

    Livewire\Livewire::test(ListUsers::class)
        ->callAction('unduhTemplate')
        ->assertNotNotified('Lengkapi data Wilayah dulu');
});

it('template builder menghasilkan sheet Data Warga, Petunjuk, dan Referensi', function () {
    $spreadsheet = app(WargaTemplateBuilder::class)->build($this->admin);

    expect($spreadsheet->getSheetNames())->toContain('Data Warga', 'Petunjuk', 'Referensi')
        ->and($spreadsheet->getSheetByName('Data Warga')->getCell('A1')->getValue())->toContain('Nama');
});
