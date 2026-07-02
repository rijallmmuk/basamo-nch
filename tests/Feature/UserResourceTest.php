<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Desa;
use App\Models\Penduduk;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Support\DesaContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('super admin (konteks desa) membuat warga lengkap dengan penduduk tertaut', function () {
    actingAs(User::factory()->superAdmin()->create());
    $desa = Desa::factory()->create();

    // Masuk konteks desa → desa dipaksa, tanpa pemilih desa (identik panel admin desa).
    DesaContext::set($desa->id);

    $data = wargaFormData($desa, '3201000000000123');
    $unitId = $data['desa_unit_id'];
    unset($data['desa_unit_id']);

    Livewire::test(CreateUser::class)
        ->fillForm($data)
        ->fillForm(['desa_unit_id' => $unitId])
        ->call('create')
        ->assertHasNoFormErrors();

    $warga = User::where('nik', '3201000000000123')->first();

    expect($warga)->not->toBeNull()
        ->and($warga->role)->toBe('warga')
        ->and($warga->desa_id)->toBe($desa->id)
        ->and($warga->penduduk)->not->toBeNull()
        ->and($warga->penduduk->nama)->toBe($warga->name)
        ->and($warga->penduduk->desa_id)->toBe($desa->id)
        ->and($warga->initial_otp)->toBeNull(); // OTP tidak auto-generate
});

it('hapus warga (soft) menyisakan identitas penduduk; bisa dipulihkan', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'nik' => '3201000000000124']);
    $penduduk = Penduduk::create(['nik' => '3201000000000124', 'nama' => $warga->name, 'desa_id' => $desa->id]);
    $warga->forceFill(['penduduk_id' => $penduduk->id])->save();

    $warga->delete();

    expect($warga->fresh()->trashed())->toBeTrue()
        ->and(Penduduk::find($penduduk->id))->not->toBeNull(); // identitas tetap

    $warga->restore();
    expect($warga->fresh()->trashed())->toBeFalse();
});

it('hanya warga yang tampil: admin & lintas-desa tak muncul', function () {
    $desaA = Desa::factory()->create(['nama' => 'Desa A']);
    $desaB = Desa::factory()->create(['nama' => 'Desa B']);

    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);
    actingAs($admin);

    $wargaA = User::factory()->warga()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$wargaA])
        ->assertCanNotSeeTableRecords([$wargaB, $admin]); // beda desa + akun admin tak muncul
});

it('super admin dalam konteks desa hanya melihat warga desa itu (bukan desa lain/admin)', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    actingAs(User::factory()->superAdmin()->create());

    $wargaA = User::factory()->warga()->create(['desa_id' => $desaA->id]);
    $wargaB = User::factory()->warga()->create(['desa_id' => $desaB->id]);
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);

    // Masuk konteks "kelola warga Desa A" (seperti klik aksi "Kelola Warga").
    DesaContext::set($desaA->id);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$wargaA])
        ->assertCanNotSeeTableRecords([$wargaB, $admin]);
});

it('form tak punya field peran; akun dibuat selalu sebagai warga', function () {
    $desa = Desa::factory()->create(['nama' => 'Desa A']);
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    // Resource khusus warga → tak ada field peran sama sekali.
    Livewire::test(CreateUser::class)
        ->assertFormFieldDoesNotExist('role')
        ->fillForm(wargaFormData($desa, '3201000000000999'))
        ->call('create')
        ->assertHasNoFormErrors();

    $warga = User::where('nik', '3201000000000999')->first();
    expect($warga->role)->toBe('warga')
        ->and($warga->desa_id)->toBe($desa->id);
});

it('aksi reset OTP & UMKM tersembunyi untuk warga yang sudah dihapus (soft delete)', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $warga->delete();

    Livewire::test(ListUsers::class)
        ->filterTable('trashed', 'with') // tampilkan record terhapus
        ->assertTableActionHidden('resetOtp', $warga)
        ->assertTableActionHidden('beriAksesUmkm', $warga);
});

it('menyimpan email selalu dalam huruf kecil', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($desa, '3201000000000777', ['email' => 'Budi.Santoso@Example.COM']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('nik', '3201000000000777')->value('email'))->toBe('budi.santoso@example.com');
});

it('menolak ubah NIK ke milik identitas penduduk lain saat edit', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));

    // Identitas orang lain (punya penduduk, belum tentu punya akun).
    Penduduk::create(['nik' => '3201000000000010', 'nama' => 'Orang Lain', 'desa_id' => $desa->id]);

    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'nik' => '3201000000000011']);
    $penduduk = Penduduk::create(['nik' => '3201000000000011', 'nama' => $warga->name, 'desa_id' => $desa->id]);
    $warga->forceFill(['penduduk_id' => $penduduk->id])->save();

    Livewire::test(EditUser::class, ['record' => $warga->getKey()])
        ->fillForm(['nik' => '3201000000000010']) // NIK milik identitas lain
        ->call('save')
        ->assertHasFormErrors(['nik']);
});

it('berhasil create & edit warga mengarahkan ke halaman index (bukan edit)', function () {
    $desa = Desa::factory()->create();
    actingAs(User::factory()->desaAdmin()->create(['desa_id' => $desa->id]));
    $index = UserResource::getUrl('index');

    Livewire::test(CreateUser::class)
        ->fillForm(wargaFormData($desa, '3201000000000888'))
        ->call('create')
        ->assertRedirect($index);

    $warga = User::where('nik', '3201000000000888')->firstOrFail();

    Livewire::test(EditUser::class, ['record' => $warga->getKey()])
        ->fillForm(['name' => 'Nama Diedit'])
        ->call('save')
        ->assertRedirect($index);

    expect($warga->refresh()->name)->toBe('Nama Diedit');
});

it('warga terarsip bisa dipulihkan lewat aksi tabel Restore', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    actingAs($admin);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);
    $warga->delete();

    Livewire::test(ListUsers::class)
        ->filterTable('trashed', true)
        ->callTableAction('restore', $warga);

    expect($warga->fresh()->trashed())->toBeFalse();
});

it('force-delete warga menghapus permanen akun + lapak UMKM beserta foto produknya', function () {
    Storage::fake(config('media-library.disk_name'));

    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    actingAs($admin);

    $warga = User::factory()->warga()->create([
        'desa_id' => $desa->id,
        'umkm_access_granted_at' => now(),
    ]);
    $profile = UmkmProfile::factory()->create(['user_id' => $warga->id, 'desa_id' => $warga->desa_id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id]);
    $product->addMedia(UploadedFile::fake()->image('foto.jpg'))->toMediaCollection('photos');

    $warga->delete();

    Livewire::test(ListUsers::class)
        ->filterTable('trashed', true)
        ->callTableAction('forceDelete', $warga);

    expect(User::withTrashed()->find($warga->id))->toBeNull()
        ->and(UmkmProfile::withTrashed()->find($profile->id))->toBeNull()
        ->and(UmkmProduct::withTrashed()->find($product->id))->toBeNull()
        ->and(Media::where('model_type', UmkmProduct::class)->where('model_id', $product->id)->count())->toBe(0);
});
