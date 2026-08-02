<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminPreviewController extends Controller
{
    /**
     * Detail/pengantar modul dalam mode pratinjau admin.
     */
    public function moduleShow(Module $module): View
    {
        $this->checkAdmin();

        $module->load(['materis' => fn ($q) => $q->orderBy('urutan'), 'prerequisite', 'pelatihan.creator.nagari', 'pelatihan.pengajars', 'media']);

        $materis = $module->materis;
        $materiSelesai = $materis->pluck('id')->all();
        $progress = null;
        $isCompleted = true;

        $evaluasi = $module->evaluasiKegiatan()->first();
        $evaluasiPassedScore = null;
        $butuhPretest = false;
        $isPreview = true;

        return view('portal.modules.show', compact(
            'module', 'materis', 'progress', 'materiSelesai', 'isCompleted', 'evaluasi', 'evaluasiPassedScore', 'butuhPretest', 'isPreview'
        ));
    }

    /**
     * Tampilkan pratinjau halaman materi (Materi) tertentu untuk administrator.
     */
    public function moduleMateri(Module $module, ?Materi $materi = null): View
    {
        $this->checkAdmin();

        $module->load(['materis' => fn ($q) => $q->orderBy('urutan')]);

        $materis = $module->materis;

        if ($materis->isEmpty()) {
            abort(404, 'Modul ini belum memiliki halaman materi.');
        }

        $currentPage = $materi && $materi->module_id === $module->id ? $materi : $materis->first();

        $currentIndex = $materis->search(fn ($p) => $p->id === $currentPage->id);
        if ($currentIndex === false) {
            $currentIndex = 0;
            $currentPage = $materis[0];
        }

        $prevMateri = $currentIndex > 0 ? $materis[$currentIndex - 1] : null;
        $nextMateri = $currentIndex < $materis->count() - 1 ? $materis[$currentIndex + 1] : null;

        $materiSelesai = $materis->pluck('id')->all();
        $materi = $currentPage;
        $isPreview = true;

        return view('portal.modules.materi', compact(
            'module', 'materi', 'materis', 'prevMateri', 'nextMateri', 'materiSelesai', 'isPreview'
        ));
    }

    /**
     * Halaman kerjakan/pratinjau pre-test dalam mode pratinjau admin.
     */
    public function pretestShow(Module $module): View|RedirectResponse
    {
        $this->checkAdmin();

        $evaluasi = $module->pretest()->first();

        if (! $evaluasi) {
            return redirect()->route('admin.preview.modules.show', $module)
                ->with('info', 'Pre-test untuk modul ini belum dibuat.');
        }

        $evaluasi->load(['pertanyaans.opsis']);
        $isPreview = true;

        return view('portal.evaluasi.show', compact('module', 'evaluasi', 'isPreview'));
    }

    /**
     * Halaman kerjakan/pratinjau evaluasi dalam mode pratinjau admin.
     */
    public function evaluasiShow(Module $module): View|RedirectResponse
    {
        $this->checkAdmin();

        $evaluasi = $module->evaluasiKegiatan()->first();

        if (! $evaluasi) {
            return redirect()->route('admin.preview.modules.show', $module)
                ->with('info', 'Evaluasi Kegiatan untuk modul ini belum dibuat.');
        }

        $evaluasi->load(['pertanyaans.opsis']);
        $isPreview = true;

        return view('portal.evaluasi.show', compact('module', 'evaluasi', 'isPreview'));
    }

    private function checkAdmin(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])) {
            abort(403, 'Akses khusus administrator / pengajar.');
        }
    }
}
