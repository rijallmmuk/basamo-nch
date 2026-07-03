<?php

use App\Models\Desa;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('katalog hanya menampilkan produk approved dari usaha aktif', function () {
    $desa = Desa::factory()->create();
    $aktif = UmkmProfile::factory()->create(['desa_id' => $desa->id, 'status' => 'active']);
    $nonaktif = UmkmProfile::factory()->create(['desa_id' => $desa->id, 'status' => 'inactive']);

    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $aktif->id, 'nama_produk' => 'Keripik Tampil']);
    UmkmProduct::factory()->create(['umkm_profile_id' => $aktif->id, 'nama_produk' => 'Produk Pending', 'status' => 'pending']);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $nonaktif->id, 'nama_produk' => 'Usaha Nonaktif']);

    $this->get(route('public.umkm.index'))
        ->assertOk()
        ->assertSee('Keripik Tampil')
        ->assertDontSee('Produk Pending')
        ->assertDontSee('Usaha Nonaktif');
});

it('filter kategori mempersempit hasil', function () {
    $desa = Desa::factory()->create();
    $profilA = UmkmProfile::factory()->create(['desa_id' => $desa->id]);
    $profilB = UmkmProfile::factory()->create(['desa_id' => $desa->id]);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profilA->id, 'umkm_category_id' => 1, 'nama_produk' => 'Produk Satu']);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profilB->id, 'umkm_category_id' => 2, 'nama_produk' => 'Produk Dua']);

    $this->get(route('public.umkm.index', ['kategori' => 1]))
        ->assertOk()
        ->assertSee('Produk Satu')
        ->assertDontSee('Produk Dua');
});

it('pencarian mempersempit hasil katalog', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id, 'nama_produk' => 'Keripik Singkong']);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id, 'nama_produk' => 'Rendang Daging']);

    $this->get(route('public.umkm.index', ['q' => 'keripik']))
        ->assertOk()
        ->assertSee('Keripik Singkong')
        ->assertDontSee('Rendang Daging');
});

it('detail produk approved tampil dan menambah jumlah_dilihat', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    $product = UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id, 'jumlah_dilihat' => 4]);

    $this->get(route('public.umkm.show', $product))
        ->assertOk()
        ->assertSee($product->nama_produk)
        ->assertSee('wa.me', false);

    expect($product->refresh()->jumlah_dilihat)->toBe(5);
});

it('jumlah_dilihat tidak dihitung dua kali untuk pengunjung yang sama dalam jendela throttle', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    $product = UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id, 'jumlah_dilihat' => 0]);

    // Kunjungan berulang dari IP yang sama (mis. refresh) hanya dihitung sekali.
    $this->get(route('public.umkm.show', $product))->assertOk();
    $this->get(route('public.umkm.show', $product))->assertOk();
    $this->get(route('public.umkm.show', $product))->assertOk();

    expect($product->refresh()->jumlah_dilihat)->toBe(1);
});

it('produk belum approved mengembalikan 404 di publik', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->get(route('public.umkm.show', $product))->assertNotFound();
});

it('menormalkan nomor WhatsApp ke format internasional Indonesia', function (string $input, string $expected) {
    $profile = new UmkmProfile(['whatsapp' => $input]);

    expect($profile->whatsappUrl())->toBe('https://wa.me/'.$expected);
})->with([
    'awalan 0' => ['08123456789', '628123456789'],
    'sudah 62' => ['628123456789', '628123456789'],
    'plus 62' => ['+62 812-3456-789', '628123456789'],
    'tanpa 0' => ['8123456789', '628123456789'],
    'prefix 00' => ['0062812345678', '62812345678'],
]);
