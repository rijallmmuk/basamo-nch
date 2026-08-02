<?php

return [
    // IDM (Indeks Desa Membangun) dipublikasi Kemendesa per TAHUN. Data terbaru bisa
    // tertinggal beberapa tahun (mis. di 2026 data terbaru = 2024). Auto-discovery
    // mundur dari tahun kini sebanyak year_lookback tahun untuk menemukan data terbaru
    // yang tersedia tanpa hardcode tahun.
    'year_lookback' => (int) env('IDM_YEAR_LOOKBACK', 4),

    // IDM berubah setahun sekali → cooldown menahan tombol "Perbarui" agar tak menghajar
    // endpoint tak resmi untuk data yang praktis statis sepanjang tahun.
    'refresh_cooldown_days' => (int) env('IDM_REFRESH_COOLDOWN_DAYS', 7),
];
