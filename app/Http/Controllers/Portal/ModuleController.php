<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Http\Controllers\Controller;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Services\SlcProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __construct(private readonly SlcProgressService $progressService) {}

    public function show(Module $module): View|RedirectResponse
    {
        $user = auth()->user();

        // Tak tampil (bukan published / program nonaktif / bukan nagarinya) → 404.
        if (! $module->isVisibleToWarga($user)) {
            abort(404);
        }

        // Pelaksanaan belum dibuka pengelola → pesan ramah (bukan 404).
        $module->loadMissing(['pelatihan.tema', 'pelatihan.creator.nagari', 'pelatihan.pengajars', 'media']);

        if (! $module->pelatihan?->dapatDimasuki()) {
            return redirect()->route('portal.pelatihan.show', $module->pelatihan)
                ->with('error', 'Modul ini belum dibuka oleh pengelola pelatihan.');
        }

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.pelatihan.show', $module->pelatihan)
                ->with('error', 'Selesaikan modul prasyarat terlebih dahulu.');
        }

        $materis = $module->materis;
        $progress = $this->progressService->startModule($user, $module);
        $materiSelesai = $progress?->halaman_selesai ?? [];
        $isCompleted = $progress?->status === ModuleProgressStatus::Completed;

        // Gerbang pre-test: bila diaktifkan dan belum dikerjakan, materi belum boleh dibuka.
        $butuhPretest = $this->progressService->butuhPretest($user, $module);

        // Evaluasi siap = terbit & punya soal. Nilai lulus tertinggi (null = belum lulus)
        // menentukan CTA setelah materi tuntas.
        $evaluasi = $module->evaluasiKegiatan()->ready()->first();
        $evaluasiPassedScore = $evaluasi
            ? EvaluasiPercobaan::where('user_id', $user->id)
                ->where('evaluasi_id', $evaluasi->id)
                ->where('status', StatusPercobaan::Passed)
                ->max('nilai')
            : null;

        return view('portal.modules.show', compact(
            'module', 'materis', 'progress', 'materiSelesai', 'isCompleted', 'evaluasi', 'evaluasiPassedScore', 'butuhPretest'
        ));
    }
}
