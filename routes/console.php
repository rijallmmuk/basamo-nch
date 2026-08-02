<?php

use App\Services\ProductionRetentionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Retensi tabel event (cegah membengkak di skala nasional) ──────────
// Fan-out notifikasi (publish modul global → jutaan baris) butuh pruning.
Schedule::call(fn () => app(ProductionRetentionService::class)->prune())
    ->daily()
    ->name('prune-expired-private-data')
    ->withoutOverlapping()
    ->onOneServer();

// Skor SDGs dari API Kemendesa — triwulanan (server lambat 5-16 detik/panggilan,
// hindari beban berlebih; data lama tetap tersimpan kalau endpoint down/berubah).
Schedule::command('sdgs:refresh-kemendesa')->quarterly()->withoutOverlapping()->onOneServer();

// Status IDM dari API Kemendesa — triwulanan (data tahunan; refresh berkala menangkap
// publikasi tahun baru begitu tersedia lewat auto-discovery tahun).
Schedule::command('idm:refresh-kemendesa')->quarterly()->withoutOverlapping()->onOneServer();

// Sensor EWS banjir bandang (Pilar 4) — tiap 5 menit. Ini peringatan dini, jadi
// rapat: riwayat harus tetap terkumpul walau tak seorang pun membuka halamannya,
// justru karena banjir bandang datang di jam orang tidur. Tiap perekaman sekaligus
// menghangatkan cache yang dibaca halaman publik. Pemangkasan riwayat lama menempel
// pada perintah yang sama.
Schedule::command('ews:record')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Arsip log aktivitas lama (retensi default Spatie 365 hari).
Schedule::command('activitylog:clean')->daily()->withoutOverlapping()->onOneServer();
Schedule::command('queue:prune-failed --hours='.(int) config('production.retention.failed_jobs_hours'))
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

// Memicu QueueBusy bila antrean utama melewati ambang; listener meneruskan alert
// ke kanal logging/Sentry yang dikonfigurasi.
Schedule::command('queue:monitor database:default --max=100')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Shared hosting tidak menyediakan process manager yang menjaga queue:work tetap
// hidup. Worker pendek ini dijalankan scheduler, menguras antrean lalu berhenti.
// `withoutOverlapping` mencegah dua worker menumpuk saat satu pekerjaan berjalan lama.
Schedule::command('queue:work database --queue=default --stop-when-empty --max-time=50 --tries=3 --timeout=120')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer();

// Backup terenkripsi harian, pemeriksaan kesehatan, dan retensi. Keputusan saat ini:
// backup_local di server hosting; risiko kehilangan server dan backup bersamaan diterima.
Schedule::command('backup:run --isolated')
    ->dailyAt('00:30')
    ->withoutOverlapping(180)
    ->onOneServer();
Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->withoutOverlapping(60)
    ->onOneServer();
Schedule::command('backup:monitor')
    ->dailyAt('06:00')
    ->onOneServer();
Schedule::command('ops:verify-backup')
    ->dailyAt('06:15')
    ->withoutOverlapping(30)
    ->onOneServer();
