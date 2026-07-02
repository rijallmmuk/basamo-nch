<?php

use App\Filament\Resources\Desas\Pages\CreateDesa;
use App\Filament\Resources\Desas\Pages\EditDesa;
use App\Filament\Resources\Desas\Pages\ListDesas;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\RefWilayah;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

it('membuat desa dari pilihan resmi: kode=kode wilayah + akun admin terbentuk', function () {
    actingAs(User::factory()->superAdmin()->create());

    $jenis = JenisDesa::firstOrCreate(['nama' => 'Desa']);

    RefWilayah::create(['kode' => '13', 'nama' => 'Sumatera Barat', 'level' => 1]);
    RefWilayah::create(['kode' => '13.06', 'nama' => 'Kabupaten Agam', 'level' => 2, 'parent_kode' => '13']);
    RefWilayah::create(['kode' => '13.06.01', 'nama' => 'Tanjung Mutiara', 'level' => 3, 'parent_kode' => '13.06']);
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);

    Livewire::test(CreateDesa::class)
        ->fillForm([
            'wilayah_kode' => '13.06.01.2001',
            'jenis_desa_id' => $jenis->id,
            'admin_email' => 'Admin.Tiku@Example.COM',
            'admin_kontak' => '081234567890',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $desa = Desa::where('wilayah_kode', '13.06.01.2001')->first();
    expect($desa)->not->toBeNull()
        ->and($desa->wilayah_kode)->toBe('13.06.01.2001')
        ->and($desa->nama)->toBe('Tiku Selatan')
        ->and($desa->kabupaten)->toBe('Kabupaten Agam');

    $admin = $desa->desaAdmin()->first();
    expect($admin)->not->toBeNull()
        // Username & Nama admin FIX otomatis dari desa.
        ->and($admin->username)->toBe('1306012001')      // = digit kode nagari
        ->and($admin->name)->toBe('Admin Desa Tiku Selatan') // = "Admin {nama_lengkap}"
        ->and($admin->email)->toBe('admin.tiku@example.com') // opsional, disimpan huruf kecil
        ->and($admin->phone)->toBe('6281234567890') // dinormalkan 62xxx, konsisten dgn warga
        ->and($admin->role)->toBe('desa_admin')
        ->and($admin->must_change_password)->toBeTrue()
        // Konsisten dgn warga: OTP DITUNDA saat blank (bukan auto-generate).
        ->and($admin->initial_otp)->toBeNull();
});

it('membuat desa sekaligus sub-unit awal (dedupe nama, abai kosong)', function () {
    actingAs(User::factory()->superAdmin()->create());
    $jenis = JenisDesa::firstOrCreate(['nama' => 'Desa']);
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);

    Livewire::test(CreateDesa::class)
        ->fillForm(['wilayah_kode' => '13.06.01.2001', 'jenis_desa_id' => $jenis->id])
        ->set('data.sub_units', [
            'k1' => ['nama' => 'Jorong A'],
            'k2' => ['nama' => 'jorong a'], // duplikat (abai huruf besar/kecil)
            'k3' => ['nama' => 'Jorong B'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $desa = Desa::where('wilayah_kode', '13.06.01.2001')->first();
    expect($desa->desaUnits()->pluck('nama')->sort()->values()->all())->toBe(['Jorong A', 'Jorong B']);
});

it('membuat desa dengan OTP admin awal eksplisit (konsisten model warga)', function () {
    actingAs(User::factory()->superAdmin()->create());
    $jenis = JenisDesa::firstOrCreate(['nama' => 'Desa']);
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);

    Livewire::test(CreateDesa::class)
        ->fillForm([
            'wilayah_kode' => '13.06.01.2001',
            'jenis_desa_id' => $jenis->id,
            'admin_otp' => '1234',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $admin = Desa::where('wilayah_kode', '13.06.01.2001')->first()->desaAdmin()->first();
    expect($admin->initial_otp)->toBe('1234')
        ->and($admin->username)->toBe('1306012001') // tetap otomatis dari kode
        ->and($admin->must_change_password)->toBeTrue();
});

it('aksi Reset OTP Admin (di tabel Desa) menerbitkan OTP baru', function () {
    actingAs(User::factory()->superAdmin()->create());
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);
    $desa = Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']);
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id, 'initial_otp' => null]);

    Livewire::test(ListDesas::class)
        ->callTableAction('resetOtpAdmin', $desa, ['otp' => '5678']);

    expect($admin->refresh()->initial_otp)->toBe('5678')
        ->and($admin->must_change_password)->toBeTrue();
});

it('setiap desa otomatis punya akun admin (username = kode nagari, OTP ditunda)', function () {
    actingAs(User::factory()->superAdmin()->create());

    $jenis = JenisDesa::firstOrCreate(['nama' => 'Desa']);
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);

    Livewire::test(CreateDesa::class)
        ->fillForm([
            'wilayah_kode' => '13.06.01.2001',
            'jenis_desa_id' => $jenis->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $admin = Desa::where('wilayah_kode', '13.06.01.2001')->first()->desaAdmin()->first();
    expect($admin)->not->toBeNull()
        ->and($admin->username)->toBe('1306012001')
        ->and($admin->initial_otp)->toBeNull(); // ditunda sampai Reset OTP
});

it('form edit desa memuat data akun admin yang ada', function () {
    actingAs(User::factory()->superAdmin()->create());

    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);
    $desa = Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']);
    User::factory()->desaAdmin()->create([
        'desa_id' => $desa->id,
        'username' => '1306012001',
        'phone' => '6281234567890',
    ]);

    // Username & nama admin tampil read-only; hanya No. HP yang termuat sebagai field.
    Livewire::test(EditDesa::class, ['record' => $desa->getRouteKey()])
        ->assertFormSet([
            'wilayah_kode' => '13.06.01.2001',
            'admin_kontak' => '6281234567890',
        ]);
});

it('kode wilayah desa yang sudah diarsipkan boleh dipakai ulang', function () {
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);

    $first = Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']);
    $first->delete(); // arsipkan (soft delete)

    // Kode sama dipakai lagi → tak melanggar unique (deleted_at disertakan).
    Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']);

    expect(Desa::where('wilayah_kode', '13.06.01.2001')->count())->toBe(1)             // hanya yang aktif
        ->and(Desa::withTrashed()->where('wilayah_kode', '13.06.01.2001')->count())->toBe(2);
});

it('hitungan warga tidak menyertakan akun admin desa', function () {
    $desa = Desa::factory()->create();
    User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    User::factory()->warga()->count(2)->create(['desa_id' => $desa->id]);

    expect($desa->warga()->count())->toBe(2)        // hanya warga
        ->and($desa->users()->count())->toBe(3);    // admin + 2 warga
});

it('desa yang masih punya warga tidak bisa dihapus', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    User::factory()->warga()->create(['desa_id' => $desa->id]);

    Livewire::test(ListDesas::class)
        ->callTableAction('delete', $desa);

    expect($desa->fresh()->trashed())->toBeFalse();
});

it('desa kosong bisa diarsipkan (soft delete)', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();

    Livewire::test(ListDesas::class)
        ->callTableAction('delete', $desa);

    expect($desa->fresh()->trashed())->toBeTrue();
});

it('desa dengan hanya akun admin bisa diarsipkan, dan admin ikut diarsipkan', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);

    Livewire::test(ListDesas::class)
        ->callTableAction('delete', $desa);

    expect($desa->fresh()->trashed())->toBeTrue()
        ->and($admin->fresh()->trashed())->toBeTrue();
});

it('desa_admin tidak boleh mengelola desa', function () {
    $admin = User::factory()->desaAdmin()->create();

    expect(Gate::forUser($admin)->check('viewAny', Desa::class))->toBeFalse();
});

it('force-delete desa juga menghapus permanen akun adminnya (tak jadi yatim)', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);

    // Alur nyata: arsipkan dulu (admin ikut terarsip), lalu hapus permanen.
    Livewire::test(ListDesas::class)
        ->callTableAction('delete', $desa);

    Livewire::test(ListDesas::class)
        ->filterTable('trashed', true)
        ->callTableAction('forceDelete', $desa);

    expect(Desa::withTrashed()->find($desa->id))->toBeNull()
        ->and(User::withTrashed()->find($admin->id))->toBeNull();
});
