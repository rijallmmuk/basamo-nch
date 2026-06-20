<?php

use App\Filament\Resources\UmkmProducts\Pages\ListUmkmProducts;
use App\Models\Nagari;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmProductVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('antrian verifikasi nagari_admin hanya menampilkan produk di nagarinya', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagariA->id]);

    $profilA = UmkmProfile::factory()->create(['nagari_id' => $nagariA->id]);
    $profilB = UmkmProfile::factory()->create(['nagari_id' => $nagariB->id]);
    $milikSendiri = UmkmProduct::factory()->create(['umkm_profile_id' => $profilA->id, 'nama_produk' => 'Produk Nagari A']);
    $nagariLain = UmkmProduct::factory()->create(['umkm_profile_id' => $profilB->id, 'nama_produk' => 'Produk Nagari B']);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProducts::class)
        ->assertCanSeeTableRecords([$milikSendiri])
        ->assertCanNotSeeTableRecords([$nagariLain]);
});

it('menyetujui produk menyetel jejak verifikasi dan memberi tahu pemilik', function () {
    Notification::fake();

    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $owner = User::factory()->umkmOwner()->create(['nagari_id' => $nagari->id]);
    $profile = UmkmProfile::factory()->create(['nagari_id' => $nagari->id, 'user_id' => $owner->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProducts::class)
        ->callTableAction('approve', $product);

    $product->refresh();
    expect($product->status)->toBe('approved')
        ->and($product->approved_by)->toBe($admin->id)
        ->and($product->approved_at)->not->toBeNull();

    Notification::assertSentTo($owner, UmkmProductVerified::class);
});

it('menolak produk menyimpan alasan dan mengembalikan status', function () {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $profile = UmkmProfile::factory()->create(['nagari_id' => $nagari->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProducts::class)
        ->callTableAction('reject', $product, data: ['rejection_reason' => 'Foto kurang jelas']);

    $product->refresh();
    expect($product->status)->toBe('rejected')
        ->and($product->rejection_reason)->toBe('Foto kurang jelas')
        ->and($product->approved_by)->toBeNull()
        ->and($product->approved_at)->toBeNull();
});

it('warga biasa tidak boleh mengakses antrian verifikasi', function () {
    $warga = User::factory()->warga()->create();

    $this->actingAs($warga);

    Livewire::test(ListUmkmProducts::class)->assertForbidden();
});
