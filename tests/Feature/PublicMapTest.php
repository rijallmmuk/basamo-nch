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
    RefWilayah::create(['kode' => '13', 'nama' => 'Sumatera Barat', 'level' => 1]);
    RefWilayah::create([
        'kode' => '13.06', 'nama' => 'Kabupaten Agam', 'level' => 2, 'parent_kode' => '13',
        'lat' => -0.3, 'lng' => 100.1, 'luas' => 2232.3, 'penduduk' => 500000,
        'path' => '[[[-0.3,100.1],[-0.4,100.2],[-0.2,100.3]]]',
    ]);
    RefWilayah::create(['kode' => '13.05', 'nama' => 'Kabupaten Padang Pariaman', 'level' => 2, 'parent_kode' => '13', 'path' => '[]']);
    RefWilayah::create(['kode' => '13.06.01', 'nama' => 'Tanjung Mutiara', 'level' => 3, 'parent_kode' => '13.06']);
    RefWilayah::create(['kode' => '13.06.01.2001', 'nama' => 'Tiku Selatan', 'level' => 4, 'parent_kode' => '13.06.01']);

    Desa::factory()->create(['wilayah_kode' => '13.06.01.2001']); // aktif (default)

    $this->get(route('public.peta.data'))
        ->assertOk()
        ->assertJsonFragment(['kode' => '13.06', 'desa_terdaftar' => 1])
        ->assertJsonFragment(['kode' => '13.05', 'desa_terdaftar' => 0]);
});
