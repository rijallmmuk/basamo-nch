<?php

use App\Models\Desa;
use App\Models\RefWilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('halaman peta publik bisa dibuka tanpa login', function () {
    $this->get(route('public.peta'))
        ->assertOk()
        ->assertSee('Peta Desa');
});

it('endpoint data peta mengembalikan kab/kota + jumlah desa terdaftar', function () {
    skipUnlessSpatial();

    RefWilayah::create([
        'kode' => '13.06', 'nama' => 'Kabupaten Agam', 'level' => 2, 'parent_kode' => '13',
        'lat' => -0.3, 'lng' => 100.1, 'luas' => 2232.3, 'penduduk' => 500000,
    ]);
    RefWilayah::create(['kode' => '13.05', 'nama' => 'Kabupaten Padang Pariaman', 'level' => 2, 'parent_kode' => '13']);

    seedBoundary('13.06', 2, 'Kabupaten Agam', 'MULTIPOLYGON(((100.1 -0.3,100.2 -0.4,100.3 -0.2,100.1 -0.3)))', '13');
    seedBoundary('13.05', 2, 'Kabupaten Padang Pariaman', 'MULTIPOLYGON(((100.0 -0.5,100.1 -0.6,100.2 -0.4,100.0 -0.5)))', '13');

    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);
    Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']); // aktif (default)

    $this->get(route('public.peta.data'))
        ->assertOk()
        ->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonPath('features.0.geometry.type', 'MultiPolygon')
        ->assertJsonFragment(['kode' => '13.06', 'desa_terdaftar' => 1])
        ->assertJsonFragment(['kode' => '13.05', 'desa_terdaftar' => 0]);
});

it('endpoint data peta drill-down mengembalikan desa dalam kab + tanda terdaftar', function () {
    skipUnlessSpatial();

    seedBoundary('13.06.01.2001', 4, 'Tiku Selatan', 'MULTIPOLYGON(((100.1 -0.3,100.2 -0.4,100.3 -0.2,100.1 -0.3)))', '13.06.01');
    seedBoundary('13.06.01.2002', 4, 'Tiku Utara', 'MULTIPOLYGON(((100.0 -0.5,100.1 -0.6,100.2 -0.4,100.0 -0.5)))', '13.06.01');

    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);
    Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']); // terdaftar

    $this->get(route('public.peta.data', ['kab' => '13.06']))
        ->assertOk()
        ->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonCount(2, 'features')
        ->assertJsonFragment(['kode' => '13.06.01.2001', 'terdaftar' => true])
        ->assertJsonFragment(['kode' => '13.06.01.2002', 'terdaftar' => false]);
});
