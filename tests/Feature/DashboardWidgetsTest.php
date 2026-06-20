<?php

use App\Filament\Widgets\AktivitasBelajarChart;
use App\Filament\Widgets\LmsProgresChart;
use App\Filament\Widgets\PlatformStatsWidget;
use App\Filament\Widgets\UmkmKategoriChart;
use App\Models\Nagari;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

$widgets = [PlatformStatsWidget::class, LmsProgresChart::class, UmkmKategoriChart::class, AktivitasBelajarChart::class];

it('merender widget dashboard untuk super_admin', function (string $widget) {
    $nagari = Nagari::factory()->create();
    $profile = UmkmProfile::factory()->create(['nagari_id' => $nagari->id]);
    UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test($widget)->assertOk();
})->with($widgets);

it('merender widget dashboard untuk nagari_admin (scope nagari)', function (string $widget) {
    $nagari = Nagari::factory()->create();
    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagari->id]);
    $profile = UmkmProfile::factory()->create(['nagari_id' => $nagari->id]);
    UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test($widget)->assertOk();
})->with($widgets);

it('menghitung produk menunggu verifikasi per nagari', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();

    $profileA = UmkmProfile::factory()->create(['nagari_id' => $nagariA->id]);
    $profileB = UmkmProfile::factory()->create(['nagari_id' => $nagariB->id]);
    UmkmProduct::factory()->count(2)->create(['umkm_profile_id' => $profileA->id, 'status' => 'pending']);
    UmkmProduct::factory()->create(['umkm_profile_id' => $profileB->id, 'status' => 'pending']);

    $admin = User::factory()->nagariAdmin()->create(['nagari_id' => $nagariA->id]);
    $this->actingAs($admin);

    Livewire::test(PlatformStatsWidget::class)
        ->assertSee('Produk menunggu')
        ->assertSee('Perlu verifikasi');
});
