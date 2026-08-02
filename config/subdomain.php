<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subdomain yang tidak boleh dipakai nagari
    |--------------------------------------------------------------------------
    |
    | Slug nagari dipakai apa adanya sebagai subdomain publik
    | (`{slug}.basamonch.com`), jadi ia berbagi ruang nama dengan subdomain
    | layanan milik hosting dan surel. Nagari yang kebetulan memakai salah satu
    | nama di bawah akan bertabrakan di tingkat DNS, dan situsnya tidak akan
    | pernah bisa dibuka, berapa kali pun aplikasinya diperbaiki.
    |
    | `www` wajib ada di sini dan juga dikecualikan di routes/web.php: tanpa itu
    | www diperlakukan sebagai slug nagari lalu 404, dan situs induk mati bagi
    | siapa pun yang mengetiknya.
    |
    | Tambahkan nama baru di sini bila kelak memakai subdomain layanan lain
    | (misalnya `status`, `docs`, atau `staging`).
    |
    */

    'reserved' => [
        // Situs induk & varian umumnya
        'www', 'web', 'apex', 'root',

        // Layanan hosting bawaan (Hostinger dan cPanel pada umumnya)
        'cpanel', 'whm', 'webmail', 'mail', 'smtp', 'imap', 'pop', 'pop3',
        'ftp', 'sftp', 'ssh', 'cdn', 'ns', 'ns1', 'ns2', 'dns', 'mx',
        'autodiscover', 'autoconfig', 'localhost',

        // Kemungkinan pemakaian internal aplikasi di kemudian hari
        'admin', 'panel', 'api', 'app', 'assets', 'static', 'media',
        'storage', 'dev', 'test', 'staging', 'demo', 'beta',
    ],

];
