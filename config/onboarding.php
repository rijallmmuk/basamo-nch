<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Awal Bersama
    |--------------------------------------------------------------------------
    |
    | Sesuai keputusan final client, akun yang dibuatkan operator memakai satu
    | password awal yang sama sampai pemiliknya menggantinya saat pertama masuk.
    | Nilainya datang dari environment; hanya hash-nya yang disimpan pada record
    | pengguna.
    |
    | JANGAN menuliskan nilainya di berkas ini. Berkas config ikut ke repo,
    | environment tidak.
    |
    */
    'initial_password' => env('INITIAL_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Password Awal per Peran
    |--------------------------------------------------------------------------
    |
    | Opsional. Isi hanya bila satu peran perlu password awal yang berbeda dari
    | password bersama di atas, misalnya karena akun superadmin dibagikan lewat
    | jalur berbeda dengan akun warga. Peran yang dikosongkan otomatis memakai
    | `initial_password`.
    |
    */
    'initial_passwords' => [
        'superadmin' => env('INITIAL_PASSWORD_SUPERADMIN'),
        'operator' => env('INITIAL_PASSWORD_OPERATOR'),
        'pengajar' => env('INITIAL_PASSWORD_PENGAJAR'),
        'dpmd' => env('INITIAL_PASSWORD_DPMD'),
        'warga' => env('INITIAL_PASSWORD_WARGA'),
    ],
];
