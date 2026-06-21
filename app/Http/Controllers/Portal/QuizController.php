<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Services\LmsProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(private readonly LmsProgressService $progressService) {}

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

        $quiz = $module->quiz;

        if (! $quiz) {
            return redirect()->route('portal.modules.show', $module);
        }

        // Kuis belum siap bila belum ada soal — jangan biarkan warga "gagal" tanpa soal.
        if ($quiz->questions()->doesntExist()) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Kuis untuk modul ini belum tersedia.');
        }

        if (! $this->progressService->isModuleCompleted($user, $module)) {
            return redirect()->route('portal.modules.show', $module)
                ->with('error', 'Selesaikan semua halaman materi terlebih dahulu.');
        }

        // Sudah lulus
        if (QuizAttempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->where('status', 'passed')->exists()) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Anda sudah lulus kuis ini.');
        }

        // Cek batas percobaan
        if ($quiz->max_attempts > 0) {
            $attemptCount = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->whereIn('status', ['passed', 'failed'])
                ->count();

            if ($attemptCount >= $quiz->max_attempts) {
                return redirect()->route('portal.modules.show', $module)
                    ->with('error', "Batas percobaan ({$quiz->max_attempts}x) telah habis.");
            }
        }

        $quiz->load(['questions.options']);

        return view('portal.quiz.show', compact('module', 'quiz'));
    }
}
