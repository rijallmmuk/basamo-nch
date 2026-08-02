<?php

namespace App\Services;

use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Models\Evaluasi;
use App\Models\UserModuleProgress;
use App\Notifications\NewEvaluasiPublished;
use Illuminate\Support\Facades\Notification;

class SlcEvaluasiNotificationService
{
    /**
     * Pengumuman ditujukan ke warga yang SUDAH menuntaskan materi modul, jadi hanya
     * berlaku untuk Evaluasi Kegiatan (penutup modul). Pre-test adalah gerbang di
     * awal: mengumumkannya ke orang yang sudah melewatinya tidak ada gunanya.
     */
    public function notifyWhenReady(Evaluasi $evaluasi): void
    {
        if ($evaluasi->jenis === JenisEvaluasi::Pretest) {
            return;
        }

        $evaluasi = Evaluasi::query()
            ->ready()
            ->with('module.pelatihan')
            ->find($evaluasi->getKey());

        if (! $evaluasi || $evaluasi->ready_notified_at !== null) {
            return;
        }

        $module = $evaluasi->module;

        if (! $module || ! $module->isReady() || ! $module->pelatihan?->dapatDimasuki()) {
            return;
        }

        $claimed = Evaluasi::query()
            ->whereKey($evaluasi)
            ->whereNull('ready_notified_at')
            ->update(['ready_notified_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        $module->wargaSasaran()
            ->whereIn('users.id', UserModuleProgress::query()
                ->where('module_id', $module->getKey())
                ->where('status', ModuleProgressStatus::Completed)
                ->select('user_id'))
            ->chunkById(500, function ($users) use ($evaluasi): void {
                Notification::send($users, new NewEvaluasiPublished($evaluasi));
            });
    }
}
