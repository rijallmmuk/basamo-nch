<?php

namespace App\Http\Controllers\Portal;

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
        $quiz = $module->quiz;

        if (! $quiz) {
            return redirect()->route('portal.modules.show', $module);
        }

        if (! $this->progressService->isModuleCompleted($user, $module)) {
            return redirect()->route('portal.modules.show', $module)
                ->with('error', 'Selesaikan semua halaman materi terlebih dahulu.');
        }

        // Sudah lulus
        $hasPassedAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'passed')
            ->exists();

        if ($hasPassedAttempt) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Anda sudah lulus kuis ini. 🎉');
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
