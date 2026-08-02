<?php

namespace App\Support\Dashboard;

use App\Enums\ModuleProgressStatus;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Support\Str;

class PengajarModuleProgressData
{
    /** @return array<string, mixed> */
    public static function options(User $user): array
    {
        $modules = Module::query()
            ->manageableBy($user)
            ->withCount('progress as activity_count')
            ->orderByDesc('activity_count')
            ->orderBy('urutan')
            ->orderBy('id')
            ->limit(8)
            ->get();

        $labels = [];
        $completedData = [];
        $inProgressData = [];
        $notStartedData = [];

        foreach ($modules as $module) {
            $targetUserIds = $module->wargaSasaran()->pluck('users.id');
            $counts = UserModuleProgress::query()
                ->where('module_id', $module->id)
                ->whereIn('user_id', $targetUserIds)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');
            $target = $targetUserIds->count();
            $completed = (int) $counts->get(ModuleProgressStatus::Completed->value, 0);
            $inProgress = (int) $counts->get(ModuleProgressStatus::InProgress->value, 0);

            $labels[] = Str::limit($module->judul, 30);
            $completedData[] = $completed;
            $inProgressData[] = $inProgress;
            $notStartedData[] = max(0, $target - $completed - $inProgress);
        }

        if ($labels === []) {
            $labels = ['Belum ada modul'];
            $completedData = [0];
            $inProgressData = [0];
            $notStartedData = [0];
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 360,
                'stacked' => true,
                'toolbar' => ['show' => false],
                'fontFamily' => 'inherit',
            ],
            'colors' => ['#10b981', '#0284c7', '#cbd5e1'],
            'series' => [
                ['name' => 'Selesai', 'data' => $completedData],
                ['name' => 'Sedang Belajar', 'data' => $inProgressData],
                ['name' => 'Belum Mulai', 'data' => $notStartedData],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
                'title' => ['text' => 'Jumlah warga'],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontSize' => '11px', 'fontFamily' => 'inherit']],
            ],
            'legend' => ['position' => 'top', 'horizontalAlign' => 'center', 'fontSize' => '12px'],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                    'barHeight' => '55%',
                ],
            ],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
            ],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
            ],
        ];
    }
}
