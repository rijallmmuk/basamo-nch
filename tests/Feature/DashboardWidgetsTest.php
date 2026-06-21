<?php

use App\Filament\Widgets\AktivitasBelajarChart;
use App\Filament\Widgets\LmsProgresChart;
use App\Filament\Widgets\PlatformStatsWidget;
use App\Filament\Widgets\UmkmKategoriChart;
use App\Models\Desa;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

$widgets = [PlatformStatsWidget::class, LmsProgresChart::class, UmkmKategoriChart::class, AktivitasBelajarChart::class];

it('merender widget dashboard untuk super_admin', function (string $widget) {
    $desa = Desa::factory()->create();
    $profile = UmkmProfile::factory()->create(['desa_id' => $desa->id]);
    UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test($widget)->assertOk();
})->with($widgets);

it('merender widget dashboard untuk desa_admin (scope desa)', function (string $widget) {
    $desa = Desa::factory()->create();
    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desa->id]);
    $profile = UmkmProfile::factory()->create(['desa_id' => $desa->id]);
    UmkmProduct::factory()->create(['umkm_profile_id' => $profile->id, 'status' => 'pending']);

    $this->actingAs($admin);

    Livewire::test($widget)->assertOk();
})->with($widgets);

it('menghitung produk menunggu verifikasi per desa', function () {
    $desaA = Desa::factory()->create();
    $desaB = Desa::factory()->create();

    $profileA = UmkmProfile::factory()->create(['desa_id' => $desaA->id]);
    $profileB = UmkmProfile::factory()->create(['desa_id' => $desaB->id]);
    UmkmProduct::factory()->count(2)->create(['umkm_profile_id' => $profileA->id, 'status' => 'pending']);
    UmkmProduct::factory()->create(['umkm_profile_id' => $profileB->id, 'status' => 'pending']);

    $admin = User::factory()->desaAdmin()->create(['desa_id' => $desaA->id]);
    $this->actingAs($admin);

    Livewire::test(PlatformStatsWidget::class)
        ->assertSee('Produk menunggu')
        ->assertSee('Perlu verifikasi');
});
