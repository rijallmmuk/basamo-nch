<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\XpLog;
use Illuminate\View\View;

class XpController extends Controller
{
    /**
     * Riwayat (ledger) perolehan XP warga — sumber: modul, kuis, diskusi.
     */
    public function index(): View
    {
        $user = auth()->user();

        $logs = XpLog::where('user_id', $user->id)
            ->latest()
            ->paginate(25);

        // Tiap entri terkait sebuah modul: 'module' & 'discussion' langsung via sumber_id,
        // 'quiz' via quiz.module_id. Muat modul + cover (media) sekali untuk hindari N+1.
        $quizModuleMap = Quiz::whereIn('id', $logs->where('sumber', 'quiz')->pluck('sumber_id')->unique())
            ->pluck('module_id', 'id'); // [quiz_id => module_id]

        $moduleIds = $logs->whereIn('sumber', ['module', 'discussion'])->pluck('sumber_id')
            ->merge($quizModuleMap->values())
            ->filter()
            ->unique();

        $modules = Module::with('media')->whereIn('id', $moduleIds)->get()->keyBy('id');

        return view('portal.xp.index', [
            'logs' => $logs,
            'modules' => $modules,
            'quizModuleMap' => $quizModuleMap,
            'totalXp' => (int) $user->total_xp,
        ]);
    }
}
