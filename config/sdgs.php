<?php

return [
    // SDGs Desa jarang berubah (skor Kemendesa diperbarui kira-kira tahunan; job
    // terjadwal berjalan tiap 3 bulan). Cooldown ini menahan tombol "Perbarui" agar
    // tak menghajar endpoint tak resmi terlalu sering — perbarui manual hanya berguna
    // untuk koreksi/nagari baru, bukan dipencet berulang.
    'refresh_cooldown_days' => (int) env('SDGS_REFRESH_COOLDOWN_DAYS', 7),
];
