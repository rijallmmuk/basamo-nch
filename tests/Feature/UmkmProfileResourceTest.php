<?php

use App\Filament\Resources\UmkmProfiles\Pages\CreateUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\EditUmkmProfile;
use App\Filament\Resources\UmkmProfiles\Pages\ListUmkmProfiles;
use App\Filament\Resources\UmkmProfiles\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\Desa;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmProductVerified;
use App\Support\DesaContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('desa_admin hanya melihat profil UMKM desanya', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();

    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);
    $milik = UmkmProfile::factory()->create(['desa_id' => $desaA->id]);
    $lain = UmkmProfile::factory()->create(['desa_id' => $desaB->id]);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProfiles::class)
        ->assertCanSeeTableRecords([$milik])
        ->assertCanNotSeeTableRecords([$lain]);
});

it('super admin dalam konteks desa hanya melihat UMKM desa itu', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $this->actingAs(User::factory()->superAdmin()->create());

    $milik = UmkmProfile::factory()->create(['desa_id' => $desaA->id]);
    $lain = UmkmProfile::factory()->create(['desa_id' => $desaB->id]);

    // Masuk konteks "kelola UMKM Desa A" (seperti klik aksi "Kelola › UMKM").
    DesaContext::set($desaA->id);

    Livewire::test(ListUmkmProfiles::class)
        ->assertCanSeeTableRecords([$milik])
        ->assertCanNotSeeTableRecords([$lain]);
});

it('membuat profil UMKM mewarisi desa dari pemiliknya', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);

    $this->actingAs($admin);

    Livewire::test(CreateUmkmProfile::class)
        ->fillForm([
            'user_id' => $owner->id,
            'nama_usaha' => 'Keripik Uji',
            'whatsapp' => '08123456789',
            'alamat' => 'Pasar Nagari, blok B',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(UmkmProfile::where('nama_usaha', 'Keripik Uji')->first()->desa_id)
        ->toBe($desa->id);
});

it('admin menyetujui produk UMKM dan memberi tahu pemilik', function () {
    Notification::fake();

    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create(['desa_id' => $desa->id, 'user_id' => $owner->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $profile,
        'pageClass' => EditUmkmProfile::class,
    ])
        ->callTableAction('approve', $product);

    $product->refresh();
    expect($product->status->value)->toBe('approved')
        ->and($product->approved_by)->toBe($admin->id);

    Notification::assertSentTo($owner, UmkmProductVerified::class);
});

it('admin menolak produk UMKM dengan alasan', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create(['desa_id' => $desa->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $profile,
        'pageClass' => EditUmkmProfile::class,
    ])
        ->callTableAction('reject', $product, data: ['alasan_penolakan' => 'Foto kurang jelas']);

    $product->refresh();
    expect($product->status->value)->toBe('rejected')
        ->and($product->alasan_penolakan)->toBe('Foto kurang jelas');
});

it('desa_admin lain tidak bisa mengakses profil di luar desanya', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();

    $adminB = User::factory()->desaAdmin()->create(['desa_id' => $desaB->id]);
    $milikA = UmkmProfile::factory()->create(['desa_id' => $desaA->id]);

    $this->actingAs($adminB);

    UmkmProfileResource::getRecordRouteBindingEloquentQuery()
        ->whereKey($milikA->getKey())
        ->firstOrFail();
})->throws(ModelNotFoundException::class);
