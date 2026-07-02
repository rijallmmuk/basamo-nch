<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Enums\ModuleStatus;
use App\Enums\QuizAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
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
            ->with(['progress' => fn ($q) => $q->where('user_id', $user->id), 'prerequisite', 'media'])
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

        // Kuis siap (punya soal) per modul → modul yang materinya tuntas tapi kuisnya
        // belum lulus masih "berjalan" bagi warga (selangkah lagi), bukan selesai penuh.
        $quizIdByModule = Quiz::whereIn('module_id', $modules->pluck('id'))
            ->whereHas('questions')
            ->pluck('id', 'module_id');

        $passedQuizIds = QuizAttempt::where('user_id', $user->id)
            ->where('status', QuizAttemptStatus::Passed)
            ->whereIn('quiz_id', $quizIdByModule->values())
            ->pluck('quiz_id');

        $quizPendingMap = $quizIdByModule->map(fn ($quizId) => ! $passedQuizIds->contains($quizId));

        $priorityOrder = ['in_progress' => 0, 'available' => 2, 'completed' => 3, 'locked' => 4];

        // "Lanjutkan Belajar": 5 modul aktif (kecuali terkunci), urut prioritas
        // sedang-dipelajari → kuis-menunggu → belum dimulai → selesai penuh,
        // lalu urutan modul menaik.
        $featured = $modules
            ->reject(fn ($m) => ($statusMap[$m->id] ?? 'available') === 'locked')
            ->sortBy(function ($m) use ($statusMap, $priorityOrder, $quizPendingMap) {
                $status = $statusMap[$m->id] ?? 'available';

                $priority = $status === 'completed' && ($quizPendingMap[$m->id] ?? false)
                    ? 1 // materi tuntas, kuis menunggu — selangkah lagi
                    : ($priorityOrder[$status] ?? 5);

                return [$priority, $m->urutan];
            })->take(5)->values();

        // Progres keseluruhan (berbasis halaman materi yang selesai)
        $totalPages = $modules->sum('pages_count');
        $donePages = $modules->sum(fn ($m) => count($m->progress->first()?->halaman_selesai ?? []));
        $overallPct = $totalPages > 0 ? (int) round($donePages / $totalPages * 100) : 0;

        // Peringkat XP se-desa (Top 5 + posisi user) — hanya warga aktif.
        $wargaQuery = fn () => User::where('role', 'warga')
            ->where('status', ActiveStatus::Active)
            ->where('desa_id', $user->desa_id);

        $topUsers = $wargaQuery()
            ->with('media') // foto profil (avatarUrl) — hindari N+1 saat render top 5
            ->orderByDesc('total_xp')
            ->orderBy('name')
            ->take(5)
            ->get(['id', 'name', 'total_xp']);

        $myRank = $wargaQuery()->where('total_xp', '>', $user->total_xp ?? 0)->count() + 1;
        $totalWarga = $wargaQuery()->count();

        return view('portal.home', compact(
            'featured', 'statusMap', 'quizPendingMap', 'modules', 'overallPct',
            'topUsers', 'myRank', 'totalWarga'
        ));
    }
}
