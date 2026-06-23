<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('landing page publik tampil tanpa login', function () {
    $this->get(route('public.home'))
        ->assertOk()
        ->assertSeeText('Basamo NCH')
        ->assertSee('Katalog UMKM')
        ->assertSee('Masuk Portal');
});

it('halaman portal terproteksi mengirim header no-store (anti back-button)', function () {
    $warga = User::factory()->warga()->create(['must_change_password' => false]);

    $response = $this->actingAs($warga)->get(route('portal.home'))->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('halaman publik tidak dipaksa no-store', function () {
    // Landing publik boleh di-cache CDN; tak perlu no-store.
    $response = $this->get(route('public.home'))->assertOk();

    expect($response->headers->get('Cache-Control'))->not->toContain('no-store');
});
