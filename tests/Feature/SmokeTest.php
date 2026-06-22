<?php

use App\Models\Desa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Smoke test: pastikan halaman utama merespons tanpa error server (5xx).
 * Bukan uji perilaku detail — hanya jaring pengaman bahwa rute & view ter-render.
 * (Endpoint peta /peta/data dilewati: butuh fungsi spasial yang tak ada di sqlite.)
 */
it('halaman publik terbuka tanpa error', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    'beranda' => '/',
    'katalog umkm' => '/umkm',
    'login portal' => '/portal/login',
]);

it('halaman portal warga terbuka tanpa error', function (string $name) {
    $warga = User::factory()->warga()->create(['desa_id' => Desa::factory()->create()->id]);

    $this->actingAs($warga)->get(route($name))->assertOk();
})->with([
    'portal.home',
    'portal.modules.index',
    'portal.leaderboard',
    'portal.notifications',
    'portal.profile.edit',
    'portal.password.edit',
]);
