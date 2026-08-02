<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Penyimpanan materi privat
    |--------------------------------------------------------------------------
    |
    | Path materi tidak pernah diekspos langsung. Portal mengalirkannya melalui
    | endpoint yang memeriksa akun, sasaran nagari, status, lock, dan prasyarat.
    | Ganti ke disk S3/R2 privat saat produksi tanpa mengubah data blok materi.
    |
    */
    'material_disk' => env('SLC_MATERIAL_DISK', 'local'),
];
