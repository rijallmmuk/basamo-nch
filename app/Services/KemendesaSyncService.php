<?php

namespace App\Services;

use App\Models\IdmIndicator;
use App\Models\IdmStatus;
use App\Models\Nagari;
use App\Models\SdgAchievement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KemendesaSyncService
{
    public function exportData(): array
    {
        $nagaris = Nagari::with(['idmStatuses.indicators', 'sdgAchievements'])->get();
        return $nagaris->toArray();
    }

    public function importData(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $nagariData) {
                if (empty($nagariData['wilayah_kode'])) continue;

                $nagari = Nagari::where('wilayah_kode', $nagariData['wilayah_kode'])->first();
                if (!$nagari) continue;

                foreach ($nagariData['idm_statuses'] ?? [] as $statusData) {
                    $status = IdmStatus::updateOrCreate(
                        ['nagari_id' => $nagari->id, 'tahun' => $statusData['tahun']],
                        [
                            'skor' => $statusData['skor'],
                            'status' => $statusData['status'],
                            'target_status' => $statusData['target_status'],
                            'skor_minimal' => $statusData['skor_minimal'],
                            'penambahan' => $statusData['penambahan'],
                            'skor_iks' => $statusData['skor_iks'],
                            'skor_ike' => $statusData['skor_ike'],
                            'skor_ikl' => $statusData['skor_ikl'],
                            'fetched_at' => isset($statusData['fetched_at']) ? Carbon::parse($statusData['fetched_at']) : null,
                        ]
                    );

                    foreach ($statusData['indicators'] ?? [] as $indData) {
                        IdmIndicator::updateOrCreate(
                            ['idm_status_id' => $status->id, 'dimensi' => $indData['dimensi'], 'nomor' => $indData['nomor']],
                            [
                                'indikator' => $indData['indikator'],
                                'skor' => $indData['skor'],
                                'keterangan' => $indData['keterangan'],
                                'kegiatan' => $indData['kegiatan'],
                                'nilai' => $indData['nilai'],
                                'pelaksana' => $indData['pelaksana'],
                            ]
                        );
                    }
                }

                foreach ($nagariData['sdg_achievements'] ?? [] as $sdgData) {
                    SdgAchievement::updateOrCreate(
                        ['nagari_id' => $nagari->id, 'sdg_goal_id' => $sdgData['sdg_goal_id']],
                        [
                            'persentase' => $sdgData['persentase'],
                            'fetched_at' => isset($sdgData['fetched_at']) ? Carbon::parse($sdgData['fetched_at']) : null,
                        ]
                    );
                }
            }
        });
    }
}
