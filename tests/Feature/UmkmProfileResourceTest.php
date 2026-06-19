<?php

use App\Filament\Resources\UmkmProfiles\Pages\CreateUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\EditUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\ListUmkmProfiles;
use App\Filament\Resources\UmkmProfiles\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\Nagari;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('nagari_admin hanya melihat profil UMKM nagarinya', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();

    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagariA->id]);
    $milik = UmkmProfile::factory()->create(['nagari_id' => $nagariA->id]);
    $lain = UmkmProfile::factory()->create(['nagari_id' => $nagariB->id]);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProfiles::class)
        ->assertCanSeeTableRecords([$milik])
        ->assertCanNotSeeTableRecords([$lain]);
});

it('membuat profil UMKM mewarisi nagari dari pemiliknya', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $owner = User::factory()->umkmOwner()->create(['nagari_id' => $nagari->id]);

    $this->actingAs($admin);

    Livewire::test(CreateUmkmProfile::class)
        ->fillForm([
            'user_id' => $owner->id,
            'nama_usaha' => 'Keripik Uji',
            'umkm_category_id' => UmkmCategory::first()->id,
            'whatsapp' => '08123456789',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(UmkmProfile::where('nama_usaha', 'Keripik Uji')->first()->nagari_id)
        ->toBe($nagari->id);
});

it('admin menyetujui produk UMKM', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $profile = UmkmProfile::factory()->create(['nagari_id' => $nagari->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $profile,
        'pageClass' => EditUmkmProfile::class,
    ])
        ->callTableAction('approve', $product);

    $product->refresh();
    expect($product->status)->toBe('approved')
        ->and($product->approved_by)->toBe($admin->id);
});

it('admin menolak produk UMKM dengan alasan', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $profile = UmkmProfile::factory()->create(['nagari_id' => $nagari->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $profile,
        'pageClass' => EditUmkmProfile::class,
    ])
        ->callTableAction('reject', $product, data: ['rejection_reason' => 'Foto kurang jelas']);

    $product->refresh();
    expect($product->status)->toBe('rejected')
        ->and($product->rejection_reason)->toBe('Foto kurang jelas');
});

it('nagari_admin lain tidak bisa mengakses profil di luar nagarinya', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();

    $adminB = User::factory()->nagariAdmin()->create(['nagari_id' => $nagariB->id]);
    $milikA = UmkmProfile::factory()->create(['nagari_id' => $nagariA->id]);

    $this->actingAs($adminB);

    UmkmProfileResource::getRecordRouteBindingEloquentQuery()
        ->whereKey($milikA->getKey())
        ->firstOrFail();
})->throws(ModelNotFoundException::class);
