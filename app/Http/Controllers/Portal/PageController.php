<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleStatus;
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
        $this->guard($module, $page, $user);

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.modules.index');
        }

        $pages = $module->pages;
        $currentIndex = $pages->search(fn ($p) => $p->id === $page->id);

        $prevPage = $currentIndex > 0 ? $pages[$currentIndex - 1] : null;
        $nextPage = $currentIndex < $pages->count() - 1 ? $pages[$currentIndex + 1] : null;

        // Membuka halaman TIDAK lagi menandai selesai — warga menekan "Tandai selesai".
        $progress = $this->progressService->getProgress($user, $module);
        $pagesCompleted = $progress?->halaman_selesai ?? [];

        return view('portal.modules.page', compact(
            'module', 'page', 'pages', 'prevPage', 'nextPage', 'pagesCompleted'
        ));
    }

    /**
     * Tandai satu halaman materi selesai (aksi eksplisit warga, POST), lalu arahkan ke
     * halaman berikutnya — atau kembali ke modul bila ini halaman terakhir. Merayakan
     * bila modul baru saja tuntas.
     */
    public function complete(Module $module, ModulePage $page): RedirectResponse
    {
        $user = auth()->user();
        $this->guard($module, $page, $user);

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.modules.index');
        }

        $wasCompleted = $this->progressService->isModuleCompleted($user, $module);
        $this->progressService->markPageCompleted($user, $module, $page);

        if (! $wasCompleted && $this->progressService->isModuleCompleted($user, $module)) {
            session()->flash('celebrate', [
                'title' => 'Modul Selesai! 🎉',
                'message' => '+'.LmsPointService::MODULE_XP.' XP ditambahkan.',
            ]);
        }

        $pages = $module->pages;
        $currentIndex = $pages->search(fn ($p) => $p->id === $page->id);
        $nextPage = $currentIndex < $pages->count() - 1 ? $pages[$currentIndex + 1] : null;

        return $nextPage
            ? redirect()->route('portal.modules.pages.show', [$module, $nextPage])
            : redirect()->route('portal.modules.show', $module);
    }

    /** Validasi akses: modul published & sedesa/global, halaman milik modul ini. */
    private function guard(Module $module, ModulePage $page, $user): void
    {
        if ($module->status !== ModuleStatus::Published ||
            ($module->desa_id !== null && $module->desa_id !== $user->desa_id)) {
            abort(404);
        }

        if ($page->module_id !== $module->id) {
            abort(404);
        }
    }
}
