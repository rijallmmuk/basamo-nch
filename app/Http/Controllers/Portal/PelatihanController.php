<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Services\SertifikatService;
use App\Services\SlcProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PelatihanController extends Controller
{
    public function __construct(
        private readonly SlcProgressService $progressService,
        private readonly SertifikatService $sertifikatService,
    ) {}

    public function index(Request $request): View
    {
        $pelatihans = Pelatihan::query()
            ->accessibleToWarga($request->user())
            ->with(['tema', 'creator:id,name,lembaga,nagari_id', 'creator.roles', 'creator.nagari:id,nama', 'pengajars:id,name,lembaga,nagari_id', 'pengajars.roles', 'pengajars.nagari:id,nama'])
            ->withCount(['modules' => fn ($modules) => $modules->ready()])
            ->latest()
            ->paginate(12);

        return view('portal.pelatihan.index', compact('pelatihans'));
    }

    public function show(Request $request, Pelatihan $pelatihan): View
    {
        $user = $request->user();

        abort_unless(
            Pelatihan::query()
                ->accessibleToWarga($user)
                ->whereKey($pelatihan->getKey())
                ->exists(),
            404,
        );

        $pelatihan->load(['tema', 'creator:id,name,lembaga,nagari_id', 'creator.roles', 'creator.nagari:id,nama', 'pengajars:id,name,lembaga,nagari_id', 'pengajars.roles', 'pengajars.nagari:id,nama']);

        $modules = Module::query()
            ->visibleToWarga($user)
            ->where('pelatihan_id', $pelatihan->getKey())
            ->with([
                'prerequisite:id,judul',
                'media',
                'materis:id,module_id,judul,urutan',
                'progress' => fn ($q) => $q->where('user_id', $user->id),
                'evaluasiKegiatan:id,module_id,jenis,nilai_lulus,maks_percobaan',
                'pretest:id,module_id,jenis',
            ])
            ->withCount('materis')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $sertifikatBerhak = $this->sertifikatService->berhak($user, $pelatihan);
        $sertifikatAlasan = $sertifikatBerhak
            ? null
            : $this->sertifikatService->alasanBelumBerhak($user, $pelatihan);

        return view('portal.pelatihan.show', compact(
            'pelatihan', 'modules', 'sertifikatBerhak', 'sertifikatAlasan',
        ));
    }
}
