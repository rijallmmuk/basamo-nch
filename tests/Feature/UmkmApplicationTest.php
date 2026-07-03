<?php

use App\Enums\ActiveStatus;
use App\Enums\PengajuanUmkmStatus;
use App\Enums\UmkmProductStatus;
use App\Filament\Resources\UmkmApplications\Pages\ListUmkmApplications;
use App\Filament\Resources\UmkmProducts\Pages\ListUmkmProducts;
use App\Filament\Resources\UmkmProfiles\Pages\ListUmkmProfiles;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Desa;
use App\Models\JenisSubUnit;
use App\Models\UmkmCategory;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmApplicationDecided;
use App\Services\UmkmService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake(config('media-library.disk_name'));
});

function wargaPemohon(): User
{
    return User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);
}

/** @return array<string, mixed> payload lengkap form pengajuan */
function payloadPengajuan(array $overrides = []): array
{
    return array_merge([
        'nama_usaha' => 'Keripik Uni Ros',
        'umkm_category_id' => UmkmCategory::create(['nama' => 'Kuliner '.uniqid(), 'urutan' => 1])->id,
        'whatsapp' => '081234567890',
        'deskripsi' => 'Usaha keripik balado rumahan.',
        'alamat' => 'Jorong Koto Tuo',
        'nama_produk' => 'Keripik Balado',
        'deskripsi_produk' => 'Keripik balado pedas manis khas Minang, renyah, kemasan 250gr.',
        'harga' => 25000,
        'photos' => [UploadedFile::fake()->image('keripik.jpg')],
    ], $overrides);
}

// ── Portal: form & kirim pengajuan ────────────────────────────────────

it('warga tanpa akses bisa membuka form pengajuan; pemilik dialihkan ke Produk Saya', function () {
    $warga = wargaPemohon();

    actingAs($warga)->get(route('portal.umkm.ajukan'))
        ->assertOk()
        ->assertSee('Ajukan Akses UMKM');

    $warga->update(['umkm_access_granted_at' => now()]);

    actingAs($warga)->get(route('portal.umkm.ajukan'))
        ->assertRedirect(route('portal.umkm.index'));
});

it('pengajuan lengkap membuat profil nonaktif-menunggu + produk pending + foto, dan memberi tahu admin desa', function () {
    $warga = wargaPemohon();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $warga->desa_id]);

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan())
        ->assertRedirect(route('portal.umkm.ajukan'))
        ->assertSessionHas('success');

    $profile = $warga->fresh()->umkmProfile;
    $product = $profile->products()->first();

    expect($warga->fresh()->hasUmkmAccess())->toBeFalse() // belum — menunggu tinjauan
        ->and($profile->status)->toBe(ActiveStatus::Inactive)
        ->and($profile->status_pengajuan)->toBe(PengajuanUmkmStatus::Menunggu)
        ->and($product->status)->toBe(UmkmProductStatus::Pending)
        ->and($product->harga)->toBe(25000)
        ->and($product->getMedia('photos'))->toHaveCount(1)
        ->and($admin->notifications()->count())->toBe(1); // lonceng panel admin
});

it('semua field pengajuan wajib: tanpa foto / tanpa harga ditolak validasi', function () {
    $warga = wargaPemohon();

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['photos' => []]))
        ->assertSessionHasErrors('photos');

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['harga' => null]))
        ->assertSessionHasErrors('harga');

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['whatsapp' => 'nol delapan satu dua']))
        ->assertSessionHasErrors('whatsapp');

    expect(UmkmProfile::count())->toBe(0);
});

it('pengajuan yang masih menunggu tidak bisa dikirim ulang', function () {
    $warga = wargaPemohon();

    actingAs($warga)->post(route('portal.umkm.ajukan.store'), payloadPengajuan());
    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['nama_usaha' => 'Coba Ganti']))
        ->assertRedirect(route('portal.umkm.ajukan'));

    expect(UmkmProfile::count())->toBe(1)
        ->and($warga->fresh()->umkmProfile->nama_usaha)->toBe('Keripik Uni Ros');
});

// ── Admin: setujui / tolak ────────────────────────────────────────────

it('menyetujui pengajuan: akses aktif, lapak tayang, produk ikut disetujui, warga diberi tahu', function () {
    Notification::fake();
    $warga = wargaPemohon();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $warga->desa_id]);

    $profile = app(UmkmService::class)->submitApplication(
        $warga,
        ['nama_usaha' => 'Lapak Uji', 'whatsapp' => '0812', 'deskripsi' => 'x', 'alamat' => 'y'],
        ['nama_produk' => 'Produk Uji', 'deskripsi' => 'z', 'harga' => 1000],
        [UploadedFile::fake()->image('p.jpg')],
    );

    actingAs($admin);
    Livewire::test(ListUmkmApplications::class)
        ->callAction([
            TestAction::make('tinjau')->table($profile),
            TestAction::make('setujui'),
        ]);

    $profile->refresh();
    expect($warga->fresh()->hasUmkmAccess())->toBeTrue()
        ->and($profile->status)->toBe(ActiveStatus::Active)
        ->and($profile->status_pengajuan)->toBeNull()
        ->and($profile->products()->first()->status)->toBe(UmkmProductStatus::Approved);

    Notification::assertSentTo($warga, UmkmApplicationDecided::class, fn ($n) => $n->approved === true);
});

it('menolak pengajuan wajib beralasan; warga melihat alasan & bisa mengajukan ulang', function () {
    Notification::fake();
    $warga = wargaPemohon();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $warga->desa_id]);

    $profile = app(UmkmService::class)->submitApplication(
        $warga,
        ['nama_usaha' => 'Lapak Tolak', 'whatsapp' => '0812', 'deskripsi' => 'x', 'alamat' => 'y'],
        ['nama_produk' => 'Produk', 'deskripsi' => 'z', 'harga' => 500],
        [UploadedFile::fake()->image('p.jpg')],
    );

    actingAs($admin);
    Livewire::test(ListUmkmApplications::class)
        ->callAction([
            TestAction::make('tinjau')->table($profile),
            TestAction::make('tolak'),
        ], ['alasan' => 'Foto produk kurang jelas.']);

    expect($profile->refresh()->status_pengajuan)->toBe(PengajuanUmkmStatus::Ditolak)
        ->and($warga->fresh()->hasUmkmAccess())->toBeFalse();
    Notification::assertSentTo($warga, UmkmApplicationDecided::class, fn ($n) => $n->approved === false);

    // Warga melihat alasan di halaman pengajuan, memperbaiki, lalu ajukan ulang.
    actingAs($warga)->get(route('portal.umkm.ajukan'))
        ->assertOk()
        ->assertSee('Foto produk kurang jelas.');

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['nama_usaha' => 'Lapak Revisi', 'photos' => []]))
        ->assertSessionDoesntHaveErrors(); // foto lama masih ada → tak wajib unggah lagi

    expect($profile->refresh()->status_pengajuan)->toBe(PengajuanUmkmStatus::Menunggu)
        ->and($profile->nama_usaha)->toBe('Lapak Revisi');
});

it('antrean pengajuan ter-scope: admin desa lain tidak melihatnya', function () {
    $warga = wargaPemohon();
    $adminLain = User::factory()->desaAdmin()->create(['desa_id' => Desa::factory()->create()->id]);

    $profile = app(UmkmService::class)->submitApplication(
        $warga,
        ['nama_usaha' => 'Lapak Desa A', 'whatsapp' => '0812', 'deskripsi' => 'x', 'alamat' => 'y'],
        ['nama_produk' => 'Produk', 'deskripsi' => 'z', 'harga' => 500],
        [UploadedFile::fake()->image('p.jpg')],
    );

    actingAs($adminLain);
    Livewire::test(ListUmkmApplications::class)
        ->assertCanNotSeeTableRecords([$profile]);
});

it('pemberian akses manual saat pengajuan menunggu = menyetujui pengajuan (state tak menggantung)', function () {
    Notification::fake();
    $warga = wargaPemohon();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $warga->desa_id]);

    $profile = app(UmkmService::class)->submitApplication(
        $warga,
        ['nama_usaha' => 'Lapak Manual', 'whatsapp' => '0812', 'deskripsi' => 'x', 'alamat' => 'y'],
        ['nama_produk' => 'Produk', 'deskripsi' => 'z', 'harga' => 500],
        [UploadedFile::fake()->image('p.jpg')],
    );

    actingAs($admin);
    Livewire::test(ListUsers::class)
        ->callTableAction('beriAksesUmkm', $warga);

    expect($warga->fresh()->hasUmkmAccess())->toBeTrue()
        ->and($profile->refresh()->status_pengajuan)->toBeNull()
        ->and($profile->status)->toBe(ActiveStatus::Active)
        ->and($profile->products()->first()->status)->toBe(UmkmProductStatus::Approved);
});

it('profil & produk pengajuan tidak bocor ke daftar Profil UMKM dan antrean Verifikasi Produk', function () {
    $warga = wargaPemohon();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $warga->desa_id]);

    $profile = app(UmkmService::class)->submitApplication(
        $warga,
        ['nama_usaha' => 'Lapak Antre', 'whatsapp' => '0812', 'alamat' => 'y'],
        ['nama_produk' => 'Produk Antre', 'deskripsi' => 'z', 'harga' => 500],
        [UploadedFile::fake()->image('p.jpg')],
    );

    actingAs($admin);

    // Dikelola HANYA lewat menu Pengajuan UMKM — bukan dobel di dua tempat lain.
    Livewire::test(ListUmkmProfiles::class)
        ->assertCanNotSeeTableRecords([$profile]);

    Livewire::test(ListUmkmProducts::class)
        ->assertCanNotSeeTableRecords([$profile->products()->first()]);

    // Setelah disetujui, keduanya tampil normal di tempat resmi.
    app(UmkmService::class)->approveApplication($profile, $admin);

    Livewire::test(ListUmkmProfiles::class)
        ->assertCanSeeTableRecords([$profile->refresh()]);
});

it('pengajuan dari warga yang sudah diarsipkan tidak bisa disetujui (hanya bisa ditolak)', function () {
    $warga = wargaPemohon();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $warga->desa_id]);

    $profile = app(UmkmService::class)->submitApplication(
        $warga,
        ['nama_usaha' => 'Lapak Yatim', 'whatsapp' => '0812', 'alamat' => 'y'],
        ['nama_produk' => 'Produk', 'deskripsi' => 'z', 'harga' => 500],
        [UploadedFile::fake()->image('p.jpg')],
    );

    $warga->delete(); // pengaju diarsipkan saat pengajuan menggantung

    actingAs($admin);

    // Modal tinjau tetap bisa dibuka (untuk menolak), tapi tombol Setujui
    // disembunyikan — status tetap menunggu, tak ada akses terbit.
    Livewire::test(ListUmkmApplications::class)
        ->assertActionHidden([
            TestAction::make('tinjau')->table($profile),
            TestAction::make('setujui'),
        ]);

    expect($profile->refresh()->status_pengajuan)->toBe(PengajuanUmkmStatus::Menunggu)
        ->and($profile->status)->toBe(ActiveStatus::Inactive);
});

it('pengajuan tidak lagi meminta deskripsi profil; alamat tetap wajib', function () {
    $warga = wargaPemohon();

    $payload = payloadPengajuan();
    unset($payload['deskripsi']); // field deskripsi profil sudah dihapus dari form

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), $payload)
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('portal.umkm.ajukan'));

    actingAs(wargaPemohon())
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['alamat' => '']))
        ->assertSessionHasErrors('alamat');
});

// ── Panduan pengisian produk per kategori ─────────────────────────────

it('kategori bawaan ter-seed dengan panduan & contoh deskripsi produk', function () {
    expect(UmkmCategory::where('slug', 'kuliner')->value('panduan_produk'))
        ->toContain('Berat atau isi per kemasan')
        ->and(UmkmCategory::where('slug', 'kuliner')->value('contoh_deskripsi'))
        ->toContain('Keripik singkong balado')
        ->and(UmkmCategory::whereNotNull('panduan_produk')->count())->toBe(7)
        ->and(UmkmCategory::whereNotNull('contoh_deskripsi')->count())->toBe(7);
});

it('sebutan di form pengajuan mengikuti data desa warga (tidak statis)', function () {
    $desa = Desa::factory()->create(['jenis_sub_unit_id' => JenisSubUnit::firstOrCreate(['nama' => 'Dusun'])->id]);
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id]);

    actingAs($warga)
        ->get(route('portal.umkm.ajukan'))
        ->assertOk()
        ->assertSee('Dusun')
        ->assertDontSee('Jorong')
        ->assertSee('Admin Nagari meninjau') // jenis desa factory = Nagari
        ->assertDontSee('Admin Desa meninjau');
});

it('deskripsi produk pengajuan minimal 30 karakter (anti asal isi), tapi boleh rinci sampai 5000', function () {
    $warga = wargaPemohon();

    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['deskripsi_produk' => 'enak murah']))
        ->assertSessionHasErrors('deskripsi_produk');

    expect(UmkmProfile::count())->toBe(0);

    // Penjual yang mau menulis detail panjang TIDAK dicegah (batas longgar 5000).
    actingAs($warga)
        ->post(route('portal.umkm.ajukan.store'), payloadPengajuan(['deskripsi_produk' => str_repeat('Detail lengkap produk. ', 130)]))
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('portal.umkm.ajukan'));
});

it('form produk reguler menampilkan bantuan deskripsi sesuai kategori usaha & menegakkan min 30 karakter', function () {
    $desa = Desa::factory()->create();
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id, 'must_change_password' => false]);
    $kuliner = UmkmCategory::where('slug', 'kuliner')->first();
    UmkmProfile::factory()->create([
        'desa_id' => $desa->id, 'user_id' => $owner->id,
    ]);

    actingAs($owner)
        ->get(route('portal.umkm.products.create'))
        ->assertOk()
        ->assertSee('Cukup sebutkan:')
        ->assertSee('Berat atau isi per kemasan')
        ->assertSee('bisa ditanyakan pembeli lewat WhatsApp')
        ->assertSee('Keripik singkong balado'); // contoh deskripsi sekali klik

    actingAs($owner)
        ->post(route('portal.umkm.products.store'), [
            'umkm_category_id' => $kuliner->id,
            'nama_produk' => 'Uji Pendek', 'deskripsi' => 'enak', 'harga' => 1000,
            'photos' => [UploadedFile::fake()->image('p.jpg')],
        ])
        ->assertSessionHasErrors('deskripsi');
});
