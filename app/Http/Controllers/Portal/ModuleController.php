<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Module;
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
                $q->whereNull('nagari_id')
                    ->orWhere('nagari_id', $user->nagari_id);
            })
            ->with([
                'progress' => fn ($q) => $q->where('user_id', $user->id),
                'prerequisite',
            ])
            ->orderBy('order')
            ->get();

        $statusMap = $modules->mapWithKeys(
            fn ($m) => [$m->id => $this->progressService->getModuleStatus($user, $m)]
        );

        return view('portal.modules.index', compact('modules', 'statusMap'));
    }

    public function show(Module $module): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.modules.index')
                ->with('error', 'Selesaikan modul prasyarat terlebih dahulu.');
        }

        $pages = $module->pages;
        $progress = $this->progressService->getProgress($user, $module);
        $pagesCompleted = $progress?->pages_completed ?? [];
        $isCompleted = $progress?->status === 'completed';

        return view('portal.modules.show', compact('module', 'pages', 'progress', 'pagesCompleted', 'isCompleted'));
    }
}
