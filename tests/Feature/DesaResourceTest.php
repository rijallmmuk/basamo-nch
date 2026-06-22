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
            'admin_name' => 'Budi',
            'admin_username' => 'admin_tiku',
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
        ->and($admin->username)->toBe('admin_tiku')
        ->and($admin->name)->toBe('Budi')
        ->and($admin->phone)->toBe('081234567890')
        ->and($admin->role)->toBe('desa_admin')
        ->and($admin->must_change_password)->toBeTrue()
        ->and($admin->initial_otp)->not->toBeNull();
});

it('membuat desa tanpa akun admin (opsional)', function () {
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

    $desa = Desa::where('wilayah_kode', '13.06.01.2001')->first();
    expect($desa)->not->toBeNull()
        ->and($desa->desaAdmin()->exists())->toBeFalse();
});

it('form edit desa memuat data akun admin yang ada', function () {
    actingAs(User::factory()->superAdmin()->create());

    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);
    $desa = Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']);
    User::factory()->desaAdmin()->create([
        'desa_id' => $desa->id,
        'username' => 'admin_tiku',
        'name' => 'Budi',
    ]);

    Livewire::test(EditDesa::class, ['record' => $desa->getRouteKey()])
        ->assertFormSet([
            'wilayah_kode' => '13.06.01.2001',
            'admin_username' => 'admin_tiku',
            'admin_name' => 'Budi',
        ]);
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
