<?php

// Override sebagian label bawaan Filament (di-merge di atas terjemahan paket).
// Halaman Buat penuh: "Buat" → "Tambah" (selaras tombol "Tambah X"), tombol
// submit "Buat" → "Simpan" (konsisten dengan halaman Ubah).
return [
    'title' => 'Tambah :label',

    'breadcrumb' => 'Tambah',

    'form' => [
        'actions' => [
            'create' => [
                'label' => 'Simpan',
            ],
        ],
    ],
];
