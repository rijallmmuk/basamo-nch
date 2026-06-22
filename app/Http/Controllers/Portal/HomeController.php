<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\LmsProgressService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly LmsProgressService $progressService) {}

    public function index(): View
    {
        $user = auth()->user();

        $modules = Module::where('status', ModuleStatus::Published)
            ->where(function ($q) use ($user) {
                $q->whereNull('desa_id')
                    ->orWhere('desa_id', $user->desa_id);
            })
            ->with(['progress' => fn ($q) => $q->where('user_id', $user->id), 'prerequisite'])
            ->withCount('pages')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        // Ambil sekali → hitung status in-memory (hindari N+1: tanpa ini tiap modul
        // memicu query prasyarat + progres). Sejalan dengan ModuleController::index.
        $completedModuleIds = UserModuleProgress::where('user_id', $user->id)
            ->where('status', ModuleProgressStatus::Completed)
            ->pluck('module_id')
            ->all();

        $statusMap = $modules->mapWithKeys(
            fn ($m) => [$m->id => $this->progressService->getModuleStatusUsing($m, $m->progress->first(), $completedModuleIds)]
        );

        $priorityOrder = ['in_progress' => 0, 'available' => 1, 'completed' => 2, 'locked' => 3];

        $featured = $modules->sortBy(function ($m) use ($statusMap, $priorityOrder) {
            $priority = $priorityOrder[$statusMap[$m->id] ?? 'available'] ?? 4;

            return [$priority, $m->urutan];
        })->take(4)->values();

        // Progres keseluruhan (berbasis halaman materi yang selesai)
        $totalPages = $modules->sum('pages_count');
        $donePages = $modules->sum(fn ($m) => count($m->progress->first()?->halaman_selesai ?? []));
        $overallPct = $totalPages > 0 ? (int) round($donePages / $totalPages * 100) : 0;

        // Peringkat XP se-desa (Top 5 + posisi user) — hanya warga aktif.
        $wargaQuery = fn () => User::where('role', 'warga')
            ->where('status', ActiveStatus::Active)
            ->where('desa_id', $user->desa_id);

        $topUsers = $wargaQuery()
            ->orderByDesc('total_xp')
            ->orderBy('name')
            ->take(5)
            ->get(['id', 'name', 'total_xp']);

        $myRank = $wargaQuery()->where('total_xp', '>', $user->total_xp ?? 0)->count() + 1;
        $totalWarga = $wargaQuery()->count();

        return view('portal.home', compact(
            'featured', 'statusMap', 'modules', 'overallPct',
            'topUsers', 'myRank', 'totalWarga'
        ));
    }
}
