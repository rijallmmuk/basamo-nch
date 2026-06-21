<?php

use App\Models\Desa;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function umkmOwnerUser(): User
{
    $desa = Desa::factory()->create();

    return User::factory()->umkmOwner()->create([
        'desa_id' => $desa->id,
        'must_change_password' => false,
    ]);
}

it('umkm_owner bisa membuka menu Produk Saya', function () {
    $this->actingAs(umkmOwnerUser())
        ->get(route('portal.umkm.index'))
        ->assertOk()
        ->assertSee('Produk Saya');
});

it('warga biasa tidak bisa membuka menu UMKM', function () {
    $desa = Desa::factory()->create();
    $warga = User::factory()->warga()->create(['desa_id' => $desa->id, 'must_change_password' => false]);

    $this->actingAs($warga)
        ->get(route('portal.umkm.index'))
        ->assertRedirect(route('portal.home'));
});

it('pemilik membuat profil usaha (desa ikut pemilik)', function () {
    $owner = umkmOwnerUser();

    $this->actingAs($owner)
        ->post(route('portal.umkm.profile.store'), [
            'nama_usaha' => 'Keripik Sanjai',
            'umkm_category_id' => UmkmCategory::first()->id,
            'whatsapp' => '08123456789',
        ])
        ->assertRedirect(route('portal.umkm.index'));

    $profile = $owner->fresh()->umkmProfile;
    expect($profile->nama_usaha)->toBe('Keripik Sanjai')
        ->and($profile->desa_id)->toBe($owner->desa_id);
});

it('pemilik menambah produk berstatus pending dengan foto', function () {
    Storage::fake('public');
    $owner = umkmOwnerUser();
    UmkmProfile::factory()->create(['desa_id' => $owner->desa_id, 'user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('portal.umkm.products.store'), [
            'nama_produk' => 'Keripik Balado',
            'harga' => 25000,
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])
        ->assertRedirect(route('portal.umkm.index'));

    $product = UmkmProduct::first();
    expect($product->status)->toBe('pending')
        ->and($product->getMedia('photos'))->toHaveCount(2);
});

it('membatasi foto produk maksimal 5', function () {
    Storage::fake('public');
    $owner = umkmOwnerUser();
    UmkmProfile::factory()->create(['desa_id' => $owner->desa_id, 'user_id' => $owner->id]);

    $photos = collect(range(1, 6))->map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"))->all();

    $this->actingAs($owner)
        ->post(route('portal.umkm.products.store'), [
            'nama_produk' => 'Banyak Foto',
            'photos' => $photos,
        ])
        ->assertSessionHasErrors('photos');
});

it('mengubah produk mengembalikan status ke pending', function () {
    $owner = umkmOwnerUser();
    $profile = UmkmProfile::factory()->create(['desa_id' => $owner->desa_id, 'user_id' => $owner->id]);
    $product = UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id]);

    $this->actingAs($owner)
        ->put(route('portal.umkm.products.update', $product), [
            'nama_produk' => 'Nama Baru',
        ])
        ->assertRedirect(route('portal.umkm.index'));

    $product->refresh();
    expect($product->status)->toBe('pending')
        ->and($product->nama_produk)->toBe('Nama Baru')
        ->and($product->approved_by)->toBeNull();
});

it('pemilik tidak bisa mengubah produk milik orang lain', function () {
    $owner = umkmOwnerUser();
    $lain = umkmOwnerUser();
    $profileLain = UmkmProfile::factory()->create(['desa_id' => $lain->desa_id, 'user_id' => $lain->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profileLain->id]);

    $this->actingAs($owner)
        ->get(route('portal.umkm.products.edit', $product))
        ->assertForbidden();
});

it('pemilik menghapus produknya sendiri', function () {
    $owner = umkmOwnerUser();
    $profile = UmkmProfile::factory()->create(['desa_id' => $owner->desa_id, 'user_id' => $owner->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id]);

    $this->actingAs($owner)
        ->delete(route('portal.umkm.products.destroy', $product))
        ->assertRedirect(route('portal.umkm.index'));

    expect(UmkmProduct::find($product->id))->toBeNull();
});
