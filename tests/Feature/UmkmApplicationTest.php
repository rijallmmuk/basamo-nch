<?php

use App\Enums\ActiveStatus;
use App\Enums\PengajuanUmkmStatus;
use App\Enums\UmkmProductStatus;
use App\Filament\Resources\UmkmApplications\Pages\ListUmkmApplications;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Desa;
use App\Models\UmkmCategory;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmApplicationDecided;
use App\Services\UmkmService;
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
        'deskripsi_produk' => 'Pedas manis khas Minang.',
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
        ->callTableAction('tinjau', $profile);

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
        ->callTableAction('tolak', $profile, ['alasan' => 'Foto produk kurang jelas.']);

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
