<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Perangkat EWS Banjir Bandang
    |--------------------------------------------------------------------------
    |
    | Pemetaan awal panel Blynk ke nagari, dipakai EwsDeviceSeeder. Kuncinya
    | KODE WILAYAH (Kepmendagri), bukan id nagari, supaya seeder yang sama benar
    | di dev maupun produksi tanpa menebak id.
    |
    | Tokennya datang dari environment dan TIDAK PERNAH ditulis di sini: berkas
    | config ikut ke repo, environment tidak. Token Blynk bukan kredensial
    | baca-saja — endpoint `update` memakai token yang sama — jadi yang
    | memegangnya bisa menulis nilai palsu ke alat peringatan dini banjir.
    |
    | Nagari yang tokennya kosong akan dilewati seeder, bukan dibuatkan
    | perangkat tanpa token.
    |
    */
    'devices' => [
        // Tj. Haro Sikabu-kabu Pd. Panjang, Kec. Luak, Kab. Lima Puluh Kota
        '13.07.04.2001' => env('EWS_TOKEN_TANJUNG_HARO'),
        // Koto Tangah, Kec. Tilatang Kamang, Kab. Agam
        '13.06.09.2001' => env('EWS_TOKEN_KOTO_TANGAH'),
        // Pangian, Kec. Lintau Buo, Kab. Tanah Datar
        '13.04.06.2004' => env('EWS_TOKEN_PANGIAN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retensi Riwayat
    |--------------------------------------------------------------------------
    |
    | Umur maksimum baris `ews_readings` dalam hari. Perekaman berjalan tiap
    | beberapa menit sepanjang tahun, jadi tanpa pemangkasan tabel ini tumbuh
    | tanpa henti. Grafik tren hanya melihat rentang pendek.
    |
    */
    'retensi_hari' => env('EWS_RETENSI_HARI', 90),
];
