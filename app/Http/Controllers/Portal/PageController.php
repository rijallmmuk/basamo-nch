<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModulePage;
use App\Services\LmsPointService;
use App\Services\LmsProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private readonly LmsProgressService $progressService) {}

    public function show(Module $module, ModulePage $page): View|RedirectResponse
    {
        $user = auth()->user();

        // Pastikan modul published dan milik nagari user (atau global)
        if ($module->status !== 'published' ||
            ($module->nagari_id !== null && $module->nagari_id !== $user->nagari_id)) {
            abort(404);
        }

        // Pastikan halaman ini memang milik modul ini
        if ($page->module_id !== $module->id) {
            abort(404);
        }

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.modules.index');
        }

        $pages = $module->pages;
        $currentIndex = $pages->search(fn ($p) => $p->id === $page->id);

        $prevPage = $currentIndex > 0 ? $pages[$currentIndex - 1] : null;
        $nextPage = $currentIndex < $pages->count() - 1 ? $pages[$currentIndex + 1] : null;

        // Tandai halaman selesai saat dibuka; rayakan bila modul baru saja tuntas.
        $wasCompleted = $this->progressService->isModuleCompleted($user, $module);
        $this->progressService->markPageCompleted($user, $module, $page);

        if (! $wasCompleted && $this->progressService->isModuleCompleted($user, $module)) {
            session()->flash('celebrate', [
                'title' => 'Modul Selesai! 🎉',
                'message' => '+'.LmsPointService::MODULE_XP.' XP ditambahkan.',
            ]);
        }

        $progress = $this->progressService->getProgress($user, $module);
        $pagesCompleted = $progress?->pages_completed ?? [];

        return view('portal.modules.page', compact(
            'module', 'page', 'pages', 'prevPage', 'nextPage', 'pagesCompleted'
        ));
    }
}
