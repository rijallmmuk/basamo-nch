<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Retensi tabel event (cegah membengkak di skala nasional) ──────────
// Fan-out notifikasi (publish modul global → jutaan baris) butuh pruning.
Schedule::call(function () {
    DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('read_at', '<', now()->subDays(90))
        ->delete();
})->daily()->name('prune-read-notifications')->onOneServer();

// Arsip log aktivitas lama (retensi default Spatie 365 hari).
Schedule::command('activitylog:clean')->daily()->onOneServer();
