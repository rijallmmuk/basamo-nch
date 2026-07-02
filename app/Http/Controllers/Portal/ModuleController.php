<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleProgressStatus;
use App\Enums\ModuleStatus;
use App\Enums\QuizAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserModuleProgress;
use App\Services\LmsProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModuleController extends Controller
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
            ->with([
                'progress' => fn ($q) => $q->where('user_id', $user->id),
                'prerequisite',
            ])
            ->withCount('pages')
            ->orderBy('urutan')
            ->get();

        // Ambil sekali: modul yang sudah diselesaikan user → hitung status in-memory
        // (hindari N+1: tanpa ini setiap modul memicu 2 query progres).
        $completedModuleIds = UserModuleProgress::where('user_id', $user->id)
            ->where('status', ModuleProgressStatus::Completed)
            ->pluck('module_id')
            ->all();

        $statusMap = $modules->mapWithKeys(
            fn ($m) => [$m->id => $this->progressService->getModuleStatusUsing($m, $m->progress->first(), $completedModuleIds)]
        );

        // Kuis yang siap dikerjakan (punya soal) per modul → tandai modul yang materinya
        // selesai tapi kuisnya BELUM lulus, agar warga tahu masih ada langkah tersisa.
        $quizIdByModule = Quiz::whereIn('module_id', $modules->pluck('id'))
            ->whereHas('questions')
            ->pluck('id', 'module_id');

        $passedQuizIds = QuizAttempt::where('user_id', $user->id)
            ->where('status', QuizAttemptStatus::Passed)
            ->whereIn('quiz_id', $quizIdByModule->values())
            ->pluck('quiz_id');

        $quizPendingMap = $quizIdByModule->map(fn ($quizId) => ! $passedQuizIds->contains($quizId));

        return view('portal.modules.index', compact('modules', 'statusMap', 'quizPendingMap'));
    }

    public function show(Module $module): View|RedirectResponse
    {
        $user = auth()->user();

        // Pastikan modul published dan milik desa user (atau global)
        if ($module->status !== ModuleStatus::Published ||
            ($module->desa_id !== null && $module->desa_id !== $user->desa_id)) {
            abort(404);
        }

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.modules.index')
                ->with('error', 'Selesaikan modul prasyarat terlebih dahulu.');
        }

        $pages = $module->pages;
        $progress = $this->progressService->getProgress($user, $module);
        $pagesCompleted = $progress?->halaman_selesai ?? [];
        $isCompleted = $progress?->status === ModuleProgressStatus::Completed;

        // Kuis siap = punya soal. Nilai lulus tertinggi (null = belum lulus) menentukan
        // CTA: "Kerjakan Kuis" menonjol setelah materi tuntas, atau kartu "Kuis Lulus".
        $quiz = $module->quiz()->whereHas('questions')->first();
        $quizPassedScore = $quiz
            ? QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', QuizAttemptStatus::Passed)
                ->max('nilai')
            : null;

        return view('portal.modules.show', compact(
            'module', 'pages', 'progress', 'pagesCompleted', 'isCompleted', 'quiz', 'quizPassedScore'
        ));
    }
}
