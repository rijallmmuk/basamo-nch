<?php

use App\Filament\Resources\UmkmProducts\Pages\ListUmkmProducts;
use App\Models\Desa;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Notifications\UmkmProductVerified;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('antrian verifikasi desa_admin hanya menampilkan produk di desanya', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);

    $profilA = UmkmProfile::factory()->create(['desa_id' => $desaA->id]);
    $profilB = UmkmProfile::factory()->create(['desa_id' => $desaB->id]);
    $milikSendiri = UmkmProduct::factory()->create(['umkm_profile_id' => $profilA->id, 'nama_produk' => 'Produk Desa A']);
    $desaLain = UmkmProduct::factory()->create(['umkm_profile_id' => $profilB->id, 'nama_produk' => 'Produk Desa B']);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProducts::class)
        ->assertCanSeeTableRecords([$milikSendiri])
        ->assertCanNotSeeTableRecords([$desaLain]);
});

it('menyetujui produk menyetel jejak verifikasi dan memberi tahu pemilik', function () {
    Notification::fake();

    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $owner = User::factory()->umkmOwner()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create(['desa_id' => $desa->id, 'user_id' => $owner->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProducts::class)
        ->callAction([
            TestAction::make('tinjau')->table($product),
            TestAction::make('setujui'),
        ]);

    $product->refresh();
    expect($product->status->value)->toBe('approved')
        ->and($product->approved_by)->toBe($admin->id)
        ->and($product->approved_at)->not->toBeNull();

    Notification::assertSentTo($owner, UmkmProductVerified::class);
});

it('menolak produk menyimpan alasan dan mengembalikan status', function () {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create(['desa_id' => $desa->id]);
    $product = UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test(ListUmkmProducts::class)
        ->callAction([
            TestAction::make('tinjau')->table($product),
            TestAction::make('tolak'),
        ], ['alasan_penolakan' => 'Foto kurang jelas']);

    $product->refresh();
    expect($product->status->value)->toBe('rejected')
        ->and($product->alasan_penolakan)->toBe('Foto kurang jelas')
        ->and($product->approved_by)->toBeNull()
        ->and($product->approved_at)->toBeNull();
});

it('warga biasa tidak boleh mengakses antrian verifikasi', function () {
    $warga = User::factory()->warga()->create();

    $this->actingAs($warga);

    Livewire::test(ListUmkmProducts::class)->assertForbidden();
});

it('desa_admin tidak bisa memverifikasi produk desa lain (aksi buntu, status tak berubah)', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);
    $profilB = UmkmProfile::factory()->create(['desa_id' => $desaB->id]);
    $produkB = UmkmProduct::factory()->create(['umkm_profile_id' => $profilB->id, 'status' => 'pending']);

    $this->actingAs($admin);

    // Record di luar scope query tabel → Filament menolak ("Record no longer exists").
    expect(fn () => Livewire::test(ListUmkmProducts::class)->callTableAction('tinjau', $produkB))
        ->toThrow(Exception::class, 'no longer exists');

    expect($produkB->refresh()->status->value)->toBe('pending')
        ->and($produkB->approved_by)->toBeNull();
});
