<?php

use App\Filament\Resources\UmkmProfiles\Pages\CreateUmkmProfile;
use App\Models\Nagari;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function auditUmkmOwner(): User
{
    $nagari = Nagari::factory()->create();

    return User::factory()->umkmOwner()->create(['nagari_id' => $nagari->id]);
}

// ── U1: profil terhapus → katalog publik 404, bukan 500 ──────────────
it('detail produk dengan profil terhapus mengembalikan 404, bukan 500', function () {
    $profile = UmkmProfile::factory()->create(['status' => 'active']);
    $product = UmkmProduct::factory()->approved()->create(['umkm_profile_id' => $profile->id]);

    $profile->delete(); // admin soft-delete profil

    $this->get(route('public.umkm.show', $product))->assertNotFound();
});

// ── U2: profil ganda untuk pemilik sama ditolak (bukan crash unique) ─
it('menolak membuat profil kedua untuk pemilik yang sama', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $owner = User::factory()->umkmOwner()->create(['nagari_id' => $nagari->id]);
    UmkmProfile::factory()->create(['nagari_id' => $nagari->id, 'user_id' => $owner->id]);

    $this->actingAs($admin);

    Livewire::test(CreateUmkmProfile::class)
        ->fillForm([
            'user_id' => $owner->id,
            'nama_usaha' => 'Lapak Kedua',
            'umkm_category_id' => UmkmCategory::first()->id,
            'whatsapp' => '08123456789',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['user_id']);
});

// ── U4: harga harus bilangan bulat (rupiah) ──────────────────────────
it('menolak harga produk berupa desimal', function () {
    $owner = auditUmkmOwner();
    UmkmProfile::factory()->create(['nagari_id' => $owner->nagari_id, 'user_id' => $owner->id]);

    $this->actingAs($owner)
        ->from(route('portal.umkm.products.create'))
        ->post(route('portal.umkm.products.store'), [
            'nama_produk' => 'Produk Desimal',
            'harga' => 10.5,
        ])
        ->assertSessionHasErrors('harga');

    expect(UmkmProduct::where('nama_produk', 'Produk Desimal')->exists())->toBeFalse();
});
