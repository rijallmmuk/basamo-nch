<?php

namespace App\Http\Controllers\Portal;

use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Http\Controllers\Controller;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\UserModuleProgress;
use App\Services\SlcProgressService;
use App\Support\Dashboard\PintasanBelajar;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly SlcProgressService $progressService) {}

    public function index(): View
    {
        $user = auth()->user();

        // 1. Ambil pelatihan aktif yang menyasar Nagari Warga.
        $pelatihans = Pelatihan::query()
            ->accessibleToWarga($user)
            // creator/pengajars beserta peran & nagarinya dimuat di sini karena kartunya
            // menyebut pengelola: tanpa ini setiap baris memicu query sendiri.
            ->with([
                'tema',
                'creator.roles',
                'creator.nagari:id,nama',
                'pengajars.roles',
                'pengajars.nagari:id,nama',
                'modules:id,pelatihan_id,judul',
            ])
            ->withCount('modules')
            ->orderBy('id', 'desc')
            ->get();

        // 2. Ambil seluruh modul yang dapat dilihat warga nagari (termasuk pelaksanaan terkunci)
        $modules = Module::query()
            ->visibleToWarga($user)
            ->with([
                'pelatihan.tema:id,nama',
                'progress' => fn ($q) => $q->where('user_id', $user->id),
                'prerequisite:id,judul',
                'media',
                'materis:id,module_id,judul,urutan',
                'evaluasiKegiatan:id,module_id,jenis,nilai_lulus,maks_percobaan',
            ])
            ->withCount('materis')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $completedModuleIds = UserModuleProgress::where('user_id', $user->id)
            ->where('status', ModuleProgressStatus::Completed)
            ->pluck('module_id')
            ->all();

        $statusMap = $modules->mapWithKeys(
            fn ($m) => [$m->id => $this->progressService->getModuleStatusUsing($m, $m->progress->first(), $completedModuleIds)]
        );

        $evaluasiIdByModule = Evaluasi::whereIn('module_id', $modules->pluck('id'))
            ->jenis(JenisEvaluasi::Kegiatan)
            ->ready()
            ->pluck('id', 'module_id');

        $passedEvaluasiIds = EvaluasiPercobaan::where('user_id', $user->id)
            ->where('status', StatusPercobaan::Passed)
            ->whereIn('evaluasi_id', $evaluasiIdByModule->values())
            ->pluck('evaluasi_id');

        $evaluasiPendingMap = $evaluasiIdByModule->map(fn ($evaluasiId) => ! $passedEvaluasiIds->contains($evaluasiId));

        // 3. Pintasan Belajar Terakhir (hanya untuk warga yang pernah belajar).
        $pintasan = PintasanBelajar::untuk($user, $modules, $statusMap, $evaluasiPendingMap, $this->progressService);

        // 4. Hitung Progres Akumulatif
        $completedCount = count($completedModuleIds);

        // Pelatihan dianggap tuntas bila SELURUH modulnya yang terlihat warga sudah
        // selesai. Pelatihan tanpa modul terlihat tidak dihitung tuntas maupun jadi
        // penyebut, supaya angkanya tidak melompat ke 100% saat isinya belum ada.
        $modulesPerPelatihan = $modules->groupBy('pelatihan_id');
        $totalPelatihan = $modulesPerPelatihan->count();
        $pelatihanTuntasCount = $modulesPerPelatihan
            ->filter(fn ($moduls) => $moduls->every(fn (Module $module): bool => in_array($module->id, $completedModuleIds, true)))
            ->count();

        $totalMateris = $modules->sum('materis_count');
        $doneMateris = $modules->sum(function (Module $module): int {
            $completedIds = $module->progress->first()?->halaman_selesai ?? [];

            return $module->materis->whereIn('id', $completedIds)->count();
        });
        $overallPct = $totalMateris > 0 ? (int) round($doneMateris / $totalMateris * 100) : 0;

        // 5. Statistik Evaluasi Kegiatan (Pre-test adalah baseline, tidak masuk nilai akhir).
        $evaluasiAttempts = EvaluasiPercobaan::where('user_id', $user->id)
            ->whereHas('evaluasi', fn ($evaluasis) => $evaluasis
                ->where('jenis', JenisEvaluasi::Kegiatan))
            ->get();
        $totalEvaluasiPercobaans = $evaluasiAttempts->count();
        $passedAttempts = $evaluasiAttempts->where('status', StatusPercobaan::Passed);
        $passedEvaluasiCount = $passedAttempts->pluck('evaluasi_id')->unique()->count();
        $averageEvaluasiScore = $totalEvaluasiPercobaans > 0 ? (int) round($evaluasiAttempts->avg('nilai')) : 0;
        $highestEvaluasiScore = $totalEvaluasiPercobaans > 0 ? (int) $evaluasiAttempts->max('nilai') : 0;
        $evaluasiPassRate = $totalEvaluasiPercobaans > 0 ? (int) round(($passedAttempts->count() / $totalEvaluasiPercobaans) * 100) : 0;

        // 6. Data Chart (ApexCharts)
        $chartModuleLabels = [];
        $chartModuleData = [];
        $statusCounts = ['completed' => 0, 'in_progress' => 0, 'available' => 0, 'locked' => 0];

        foreach ($modules as $mod) {
            $st = $statusMap[$mod->id] ?? 'available';
            $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;

            // Hitung hanya materi yang MASIH ADA: id materi yang sudah dihapus
            // pengajar tetap tersimpan pada progres warga sampai ia menyelesaikan
            // materi berikutnya, dan tanpa penyaringan ini persentasenya bisa
            // melewati 100%.
            $pCount = $mod->materis_count;
            $pDone = $mod->materis->whereIn('id', $mod->progress->first()?->halaman_selesai ?? [])->count();
            $pct = $pCount > 0 ? (int) round($pDone / $pCount * 100) : 0;

            $chartModuleLabels[] = $mod->judul;
            $chartModuleData[] = $pct;
        }

        return view('portal.home', compact(
            'pelatihans', 'modules', 'statusMap', 'evaluasiPendingMap', 'overallPct', 'doneMateris', 'totalMateris',
            'completedCount', 'pelatihanTuntasCount', 'totalPelatihan', 'totalEvaluasiPercobaans',
            'passedEvaluasiCount', 'averageEvaluasiScore',
            'highestEvaluasiScore', 'evaluasiPassRate', 'pintasan',
            'chartModuleLabels', 'chartModuleData', 'statusCounts'
        ));
    }
}
