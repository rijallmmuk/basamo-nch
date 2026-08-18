<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Models\Discussion;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\User;
use App\Models\UserModuleProgress;

class PengajarOverviewData
{
    /**
     * @return list<array{label: string, value: string|int, description: string, icon: string, color: string}>
     */
    public static function stats(User $user): array
    {
        $programIds = Pelatihan::query()->manageableBy($user)->pluck('id');
        $moduleQuery = Module::query()->manageableBy($user);
        $moduleIds = (clone $moduleQuery)->pluck('modules.id');

        $totalProgram = $programIds->count();
        $totalModul = $moduleIds->count();
        $modulBerisi = (clone $moduleQuery)->ready()->count();

        $progress = UserModuleProgress::query()
            ->whereIn('module_id', $moduleIds)
            ->whereHas('user', fn ($users) => $users
                ->role('warga')
                ->where('users.status', ActiveStatus::Active)
                ->whereHas('nagari', fn ($nagaris) => $nagaris->where('status', ActiveStatus::Active)));
        $wargaAktif = (clone $progress)
            ->whereIn('status', [ModuleProgressStatus::InProgress, ModuleProgressStatus::Completed])
            ->distinct('user_id')
            ->count('user_id');
        $sedangBelajar = (clone $progress)->where('status', ModuleProgressStatus::InProgress)->count();
        $selesai = (clone $progress)->where('status', ModuleProgressStatus::Completed)->count();

        $evaluasiIds = Evaluasi::query()
            ->ready()
            ->where('jenis', JenisEvaluasi::Kegiatan)
            ->whereIn('module_id', $moduleIds)
            ->pluck('id');
        $attempts = EvaluasiPercobaan::query()
            ->whereIn('evaluasi_id', $evaluasiIds)
            ->whereHas('user', fn ($users) => $users
                ->role('warga')
                ->where('users.status', ActiveStatus::Active)
                ->whereHas('nagari', fn ($nagaris) => $nagaris->where('status', ActiveStatus::Active)));
        $totalPengerjaan = (clone $attempts)->count();
        $avgEvaluasiScore = (clone $attempts)->whereNotNull('nilai')->avg('nilai');
        $wargaLulus = (clone $attempts)
            ->where('status', StatusPercobaan::Passed)
            ->distinct('user_id')
            ->count('user_id');

        $topik = Discussion::query()
            ->whereIn('module_id', $moduleIds)
            ->whereNull('parent_id');
        $totalTopik = (clone $topik)->count();
        $belumDibaca = (clone $topik)
            ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user->id))
            ->count();
        $totalBalasan = Discussion::query()
            ->whereIn('module_id', $moduleIds)
            ->whereNotNull('parent_id')
            ->count();

        return [
            [
                'label' => 'Konten SLC Saya',
                'value' => "{$totalProgram} Pelatihan · {$totalModul} Modul",
                'description' => "{$modulBerisi} modul sudah memiliki materi",
                'icon' => 'heroicon-m-academic-cap',
                'color' => 'primary',
                'chart' => [2, 5, 8, $totalModul],
            ],
            [
                'label' => 'Warga Aktif Belajar',
                'value' => number_format($wargaAktif, 0, ',', '.').' Warga',
                'description' => "{$sedangBelajar} progres berjalan · {$selesai} selesai",
                'icon' => 'heroicon-m-users',
                'color' => 'info',
                'chart' => [10, 20, 35, $wargaAktif],
            ],
            [
                'label' => 'Evaluasi Kegiatan',
                'value' => $avgEvaluasiScore === null
                    ? 'Belum ada nilai'
                    : number_format((float) $avgEvaluasiScore, 1, ',', '.').' / 100',
                'description' => "{$totalPengerjaan} pengerjaan · {$wargaLulus} warga lulus",
                'icon' => 'heroicon-m-chart-bar',
                'color' => 'success',
                'chart' => [50, 70, 85, $avgEvaluasiScore ?? 0],
            ],
            [
                'label' => 'Forum Belum Dibaca',
                'value' => number_format($belumDibaca, 0, ',', '.').' Topik',
                'description' => "{$totalTopik} topik · {$totalBalasan} balasan seluruhnya",
                'icon' => 'heroicon-m-chat-bubble-left-right',
                'color' => $belumDibaca > 0 ? 'warning' : 'gray',
                'chart' => [0, 1, 2, $belumDibaca],
            ],
        ];
    }
}
