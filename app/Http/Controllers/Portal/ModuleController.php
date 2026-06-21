<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleProgressStatus;
use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
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

        $modules = Module::where('status', 'published')
            ->where(function ($q) use ($user) {
                $q->whereNull('desa_id')
                    ->orWhere('desa_id', $user->desa_id);
            })
            ->with([
                'progress' => fn ($q) => $q->where('user_id', $user->id),
                'prerequisite',
            ])
            ->withCount('pages')
            ->orderBy('sort_order')
            ->get();

        // Ambil sekali: modul yang sudah diselesaikan user → hitung status in-memory
        // (hindari N+1: tanpa ini setiap modul memicu 2 query progres).
        $completedModuleIds = UserModuleProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('module_id')
            ->all();

        $statusMap = $modules->mapWithKeys(
            fn ($m) => [$m->id => $this->progressService->getModuleStatusUsing($m, $m->progress->first(), $completedModuleIds)]
        );

        return view('portal.modules.index', compact('modules', 'statusMap'));
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
        $pagesCompleted = $progress?->pages_completed ?? [];
        $isCompleted = $progress?->status === ModuleProgressStatus::Completed;

        return view('portal.modules.show', compact('module', 'pages', 'progress', 'pagesCompleted', 'isCompleted'));
    }
}
