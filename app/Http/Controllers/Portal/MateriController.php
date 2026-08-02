<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Module;
use App\Services\SlcProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function __construct(private readonly SlcProgressService $progressService) {}

    public function show(Module $module, Materi $materi): View|RedirectResponse
    {
        $user = auth()->user();
        $this->guard($module, $materi, $user);

        $isAdmin = $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);

        if (! $isAdmin && ! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.pelatihan.show', $module->pelatihan_id);
        }

        // Pre-test adalah gerbang: materi tidak terbuka sebelum dikerjakan sekali.
        if (! $isAdmin && $this->progressService->butuhPretest($user, $module)) {
            return redirect()->route('portal.modules.pretest', $module)
                ->with('info', 'Kerjakan pre-test terlebih dahulu untuk membuka materi.');
        }

        $progress = $this->progressService->startModule($user, $module);

        // Sidebar & prev/next cuma butuh id/judul/urutan → jangan hidrasi kolom `blocks`
        // (bisa besar) untuk SEMUA halaman. Materi halaman aktif sudah termuat via $materi.
        $module->load(['materis' => fn ($q) => $q->select('id', 'module_id', 'judul', 'urutan')]);

        // Membuka halaman TIDAK lagi menandai selesai — warga menekan "Tandai selesai".
        $materiSelesai = $isAdmin
            ? $module->materis->pluck('id')->all()
            : ($progress?->halaman_selesai ?? []);

        // Materi harus berurutan (khusus warga): jika halaman ini dilewati (materi sebelumnya belum
        // selesai), arahkan kembali ke materi yang seharusnya dibaca.
        if (! $isAdmin && ! $this->progressService->isMateriAccessible($module, $materi, $materiSelesai)) {
            $resume = $this->progressService->firstIncompleteMateri($module, $materiSelesai) ?? $module->materis->first();

            return redirect()->route('portal.modules.materi.show', [$module, $resume])
                ->with('error', 'Materi harus dibaca berurutan. Selesaikan materi sebelumnya dulu, ya.');
        }

        $materis = $module->materis;
        $currentIndex = $materis->search(fn ($p) => $p->id === $materi->id);

        $prevMateri = $currentIndex > 0 ? $materis[$currentIndex - 1] : null;
        $nextMateri = $currentIndex < $materis->count() - 1 ? $materis[$currentIndex + 1] : null;

        return view('portal.modules.materi', compact(
            'module', 'materi', 'materis', 'prevMateri', 'nextMateri', 'materiSelesai'
        ));
    }

    /**
     * Tandai satu halaman materi selesai (aksi eksplisit warga, POST), lalu arahkan ke
     * halaman berikutnya — atau kembali ke modul bila ini halaman terakhir. Merayakan
     * bila modul baru saja tuntas.
     */
    public function complete(Module $module, Materi $materi): RedirectResponse
    {
        $user = auth()->user();
        $this->guard($module, $materi, $user);

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.pelatihan.show', $module->pelatihan_id);
        }

        if ($this->progressService->butuhPretest($user, $module)) {
            return redirect()->route('portal.modules.pretest', $module)
                ->with('info', 'Kerjakan pre-test terlebih dahulu untuk membuka materi.');
        }

        // Cegah "loncat selesai": halaman hanya bisa diselesaikan bila boleh diakses
        // (semua materi sebelumnya sudah selesai).
        $materiSelesai = $this->progressService->getProgress($user, $module)?->halaman_selesai ?? [];
        if (! $this->progressService->isMateriAccessible($module, $materi, $materiSelesai)) {
            return redirect()->route('portal.modules.show', $module)
                ->with('error', 'Materi harus diselesaikan berurutan.');
        }

        $wasCompleted = $this->progressService->isModuleCompleted($user, $module);
        $this->progressService->markMateriCompleted($user, $module, $materi);

        if (! $wasCompleted && $this->progressService->isModuleCompleted($user, $module)) {
            session()->flash('celebrate', [
                'title' => 'Modul Selesai! 🎉',
                // Ada Evaluasi Kegiatan siap (punya soal) → sekalian ajak lanjut (CTA-nya menonjol
                // di halaman modul yang jadi tujuan redirect).
                'message' => $module->evaluasiKegiatan()->ready()->exists()
                    ? 'Lanjut kerjakan Evaluasi Kegiatannya, ya!'
                    : 'Kerja bagus, terus lanjutkan belajarmu!',
            ]);
        }

        $materis = $module->materis;
        $currentIndex = $materis->search(fn ($p) => $p->id === $materi->id);
        $nextMateri = $currentIndex < $materis->count() - 1 ? $materis[$currentIndex + 1] : null;

        return $nextMateri
            ? redirect()->route('portal.modules.materi.show', [$module, $nextMateri])
            : redirect()->route('portal.modules.show', $module);
    }

    /** Validasi akses warga dan pastikan halaman benar-benar milik modul. */
    private function guard(Module $module, Materi $materi, $user): void
    {
        if (! $module->isAccessibleToWarga($user)) {
            abort(404);
        }

        if ($materi->module_id !== $module->id) {
            abort(404);
        }
    }
}
