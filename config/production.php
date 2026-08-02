<?php

$trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    // Aktifkan hanya bila trafik melewati Cloudflare/CDN/reverse proxy. Ketika
    // aktif, TRUSTED_PROXIES wajib berisi IP/CIDR proxy yang benar.
    'reverse_proxy_enabled' => (bool) env('REVERSE_PROXY_ENABLED', false),

    // Kanal email belum dipakai. Notifikasi pengguna tetap memakai database.
    'mail_enabled' => (bool) env('MAIL_ENABLED', false),

    'trusted_proxies' => match ($trustedProxies) {
        '' => null,
        '*' => '*',
        default => array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))),
    },

    'security_headers' => [
        'Content-Security-Policy' => "base-uri 'self'; frame-ancestors 'self'; object-src 'none'",
        'Permissions-Policy' => 'camera=(), geolocation=(), microphone=()',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
    ],

    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', env('APP_ENV') === 'production'),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
    ],

    'exception_reports_per_minute' => (int) env('EXCEPTION_REPORTS_PER_MINUTE', 60),

    // Rem login (per menit). `per_identity` = brute-force bertarget; `per_ip` = password-
    // spraying lintas identitas dari satu IP. `per_ip` sengaja longgar agar login massal
    // sah di balik satu NAT (mis. ruang pelatihan) tak terhalang; naikkan bila perlu.
    // Berapa lama "Ingat saya" bertahan. Bawaan Laravel 400 hari; itu terlalu lama
    // untuk akun back-office yang dipakai bersama di perangkat kantor. 30 hari cukup
    // menghapus keluhan "login terus menerus" tanpa meninggalkan cookie abadi.
    'remember_days' => (int) env('AUTH_REMEMBER_DAYS', 30),

    'login_throttle' => [
        'per_identity' => (int) env('LOGIN_MAX_ATTEMPTS_PER_IDENTITY', 5),
        'per_ip' => (int) env('LOGIN_MAX_ATTEMPTS_PER_IP', 100),
    ],

    'retention' => [
        'read_notifications_days' => (int) env('RETENTION_READ_NOTIFICATIONS_DAYS', 90),
        'contact_messages_days' => (int) env('RETENTION_CONTACT_MESSAGES_DAYS', 730),
        'trashed_contact_messages_days' => (int) env('RETENTION_TRASHED_CONTACT_MESSAGES_DAYS', 90),
        'failed_jobs_hours' => (int) env('RETENTION_FAILED_JOBS_HOURS', 720),
    ],

    'health' => [
        'enabled' => (bool) env('HEALTH_CHECKS_ENABLED', true),
        'checks' => [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'queue' => true,
        ],
        'storage_disks' => array_values(array_unique([
            env('FILESYSTEM_DISK', 'local'),
            env('MEDIA_DISK', 'public'),
        ])),
    ],
];
