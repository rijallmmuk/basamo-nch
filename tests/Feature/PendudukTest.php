<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\StatusPerkawinan;
use App\Models\User;
use App\Services\PendudukService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('membuat warga lewat form ikut membuat penduduk tertaut + demografi', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    $agama = Agama::firstOrCreate(['nama' => 'Islam'], ['urutan' => 1]);
    $status = StatusPerkawinan::firstOrCreate(['nama' => 'Belum Kawin'], ['urutan' => 1]);
    $kerja = Pekerjaan::firstOrCreate(['kode' => '01'], ['nama' => 'Petani/Pekebun', 'urutan' => 1]);
    $unit = DesaUnit::create(['desa_id' => $desa->id, 'nama' => 'Jorong A']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Warga Penduduk',
            'nik' => '3201010101010001',
            'tempat_lahir' => 'Bukittinggi',
            'tanggal_lahir' => '1990-05-17',
            'jenis_kelamin' => 'L',
            'agama_id' => $agama->id,
            'status_perkawinan_id' => $status->id,
            'pekerjaan_id' => $kerja->id,
            'desa_unit_id' => $unit->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $warga = User::where('nik', '3201010101010001')->firstOrFail();
    $penduduk = $warga->penduduk;

    // Akun (users) tidak lagi menyimpan demografi — hanya tautan penduduk_id.
    expect($warga->penduduk_id)->not->toBeNull()
        ->and($penduduk)->not->toBeNull()
        ->and($penduduk->nik)->toBe('3201010101010001')   // kanonik di penduduk
        ->and($warga->nik)->toBe('3201010101010001')      // mirror login di users
        ->and($penduduk->nama)->toBe('Warga Penduduk')
        ->and($penduduk->desa_id)->toBe($desa->id)
        ->and($penduduk->tempat_lahir)->toBe('Bukittinggi')
        ->and($penduduk->jenis_kelamin->value)->toBe('L')
        ->and($penduduk->agama_id)->toBe($agama->id)
        ->and($penduduk->pekerjaan_id)->toBe($kerja->id);

    // Kolom demografi tidak ada lagi di tabel users.
    expect(Schema::hasColumn('users', 'agama_id'))->toBeFalse()
        ->and(Schema::hasColumn('penduduk', 'agama_id'))->toBeTrue();
});

it('mengedit warga memperbarui penduduk yang sama (tanpa duplikat)', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'nik' => '3201010101010002']);
    $penduduk = Penduduk::create([
        'nik' => '3201010101010002', 'nama' => $warga->name, 'desa_id' => $desa->id, 'tempat_lahir' => 'Lama',
    ]);
    $warga->forceFill(['penduduk_id' => $penduduk->id])->save();

    Livewire::test(EditUser::class, ['record' => $warga->getRouteKey()])
        ->assertFormSet(['tempat_lahir' => 'Lama'])
        ->fillForm(['tempat_lahir' => 'Padang'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Penduduk::where('nik', '3201010101010002')->count())->toBe(1)
        ->and($penduduk->fresh()->tempat_lahir)->toBe('Padang');
});

it('halaman lihat warga menampilkan identitas penduduk', function () {
    $desa = Desa::factory()->create();
    $this->actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'nik' => '3201010101010005', 'name' => 'Warga Lihat']);
    $penduduk = Penduduk::create([
        'nik' => '3201010101010005', 'nama' => 'Warga Lihat', 'desa_id' => $desa->id, 'tempat_lahir' => 'Solok',
    ]);
    $warga->forceFill(['penduduk_id' => $penduduk->id])->save();

    Livewire::test(ViewUser::class, ['record' => $warga->getRouteKey()])
        ->assertOk()
        ->assertSee('Warga Lihat')
        ->assertSee('3201010101010005')
        ->assertSee('Solok');
});

it('sync memakai ulang penduduk ber-NIK sama (tanpa duplikat / unique violation)', function () {
    $desa = Desa::factory()->create();
    // Identitas sudah terdaftar lebih dulu tanpa akun.
    $orphan = Penduduk::create(['nik' => '3201000000000200', 'nama' => 'Nama Lama', 'desa_id' => $desa->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'nik' => '3201000000000200', 'name' => 'Nama Baru']);

    app(PendudukService::class)->syncForUser($warga, ['tempat_lahir' => 'Padang']);

    expect(Penduduk::where('nik', '3201000000000200')->count())->toBe(1)        // tak duplikat
        ->and($warga->fresh()->penduduk_id)->toBe($orphan->id)                  // tertaut ke yang ada
        ->and($orphan->fresh()->nama)->toBe('Nama Baru')                        // di-mirror dari akun
        ->and($orphan->fresh()->tempat_lahir)->toBe('Padang');
});

it('admin (non-warga) tidak punya penduduk', function () {
    $admin = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]);

    expect($admin->penduduk_id)->toBeNull()
        ->and($admin->penduduk)->toBeNull();
});
