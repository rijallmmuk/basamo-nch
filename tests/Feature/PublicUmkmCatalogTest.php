<?php

use App\Models\Nagari;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('katalog hanya menampilkan produk approved dari usaha aktif', function () {
    $nagari = Nagari::factory()->create();
    $aktif = UmkmProfile::factory()->create(['nagari_id' => $nagari->id, 'status' => 'active']);
    $nonaktif = UmkmProfile::factory()->create(['nagari_id' => $nagari->id, 'status' => 'inactive']);

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
    $nagari = Nagari::factory()->create();
    $profilA = UmkmProfile::factory()->create(['nagari_id' => $nagari->id, 'umkm_category_id' => 1]);
    $profilB = UmkmProfile::factory()->create(['nagari_id' => $nagari->id, 'umkm_category_id' => 2]);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profilA->id, 'nama_produk' => 'Produk Satu']);
    UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profilB->id, 'nama_produk' => 'Produk Dua']);

    $this->get(route('public.umkm.index', ['kategori' => 1]))
        ->assertOk()
        ->assertSee('Produk Satu')
        ->assertDontSee('Produk Dua');
});

it('detail produk approved tampil dan menambah view_count', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    $product = UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id, 'view_count' => 4]);

    $this->get(route('public.umkm.show', $product))
        ->assertOk()
        ->assertSee($product->nama_produk)
        ->assertSee('wa.me', false);

    expect($product->refresh()->view_count)->toBe(5);
});

it('produk belum approved mengembalikan 404 di publik', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->get(route('public.umkm.show', $product))->assertNotFound();
});
