<?php

namespace App\Http\Controllers\Portal;

use App\Enums\StatusPercobaan;
use App\Http\Controllers\Controller;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Services\SlcProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluasiController extends Controller
{
    public function __construct(private readonly SlcProgressService $progressService) {}

    /**
     * Pre-test = gerbang SEBELUM materi. Tanpa syarat nilai dan hanya sekali
     * percobaan, jadi begitu sudah dikerjakan warga langsung dialihkan ke modul.
     */
    public function pretest(Module $module): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $module->isAccessibleToWarga($user)) {
            abort(404);
        }

        $isAdmin = $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);

        if (! $isAdmin && ! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.pelatihan.show', $module->pelatihan_id)
                ->with('error', 'Selesaikan modul prasyarat terlebih dahulu.');
        }

        $evaluasi = $isAdmin
            ? $module->pretest()->first()
            : $module->pretest()->ready()->first();

        if (! $evaluasi) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Modul ini tidak memakai pre-test.');
        }

        if (! $isAdmin && $evaluasi->sudahDikerjakan($user)) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Pre-test sudah Anda kerjakan. Materi kini terbuka.');
        }

        $evaluasi->load(['pertanyaans.opsis']);

        return view('portal.evaluasi.show', compact('module', 'evaluasi'));
    }

    /** Evaluasi Kegiatan = penutup modul, dikerjakan setelah seluruh materi tuntas. */
    public function show(Module $module): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $module->isAccessibleToWarga($user)) {
            abort(404);
        }

        $isAdmin = $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);

        if (! $isAdmin && ! $this->progressService->isModuleAccessible($user, $module)) {
            return redirect()->route('portal.pelatihan.show', $module->pelatihan_id)
                ->with('error', 'Selesaikan modul prasyarat terlebih dahulu.');
        }

        $evaluasi = $isAdmin
            ? $module->evaluasiKegiatan()->first()
            : $module->evaluasiKegiatan()->ready()->first();

        if (! $evaluasi) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Evaluasi Kegiatan untuk modul ini belum tersedia.');
        }

        if (! $isAdmin && ! $this->progressService->isModuleCompleted($user, $module)) {
            return redirect()->route('portal.modules.show', $module)
                ->with('error', 'Selesaikan semua materi terlebih dahulu.');
        }

        if (! $isAdmin && EvaluasiPercobaan::where('user_id', $user->id)
            ->where('evaluasi_id', $evaluasi->id)
            ->where('status', StatusPercobaan::Passed)
            ->exists()) {
            return redirect()->route('portal.modules.show', $module)
                ->with('info', 'Anda sudah lulus Evaluasi Kegiatan ini.');
        }

        if (! $isAdmin && $evaluasi->maks_percobaan > 0) {
            $sisa = $evaluasi->sisaPercobaan($user);

            if ($sisa !== null && $sisa <= 0) {
                return redirect()->route('portal.modules.show', $module)
                    ->with('error', "Batas percobaan ({$evaluasi->maks_percobaan}x) telah habis.");
            }
        }

        $evaluasi->load(['pertanyaans.opsis']);

        return view('portal.evaluasi.show', compact('module', 'evaluasi'));
    }
}
