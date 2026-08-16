<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\WebinarAttendance;
use App\Services\SertifikatService;
use App\Services\SlcProgressService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
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

        $sudahHadir = $pelatihan->adalahWebinar() && $pelatihan->sudahHadir($user);

        return view('portal.pelatihan.show', compact(
            'pelatihan', 'modules', 'sertifikatBerhak', 'sertifikatAlasan', 'sudahHadir',
        ));
    }

    /**
     * Warga menandai dirinya mengikuti pertemuan daring.
     *
     * Ini satu-satunya bukti mengikuti yang dimiliki webinar, dan ia menjadi syarat
     * terbitnya sertifikat. Kelayakannya diperiksa ulang di sini, bukan dipercayakan
     * pada tombol yang tampil: alamatnya dapat dikirim ke server secara langsung.
     */
    public function tandaiHadir(Request $request, Pelatihan $pelatihan): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            Pelatihan::query()
                ->accessibleToWarga($user)
                ->whereKey($pelatihan->getKey())
                ->exists(),
            404,
        );

        abort_unless($pelatihan->adalahWebinar(), 404);

        $kembali = redirect()->route('portal.pelatihan.show', $pelatihan);

        if (! $pelatihan->dapatDimasuki()) {
            return $kembali->with('error', 'Pelatihan ini sedang dikunci pengelola.');
        }

        // Mustahil menghadiri pertemuan yang belum berlangsung.
        if (! $pelatihan->pertemuanSudahMulai()) {
            return $kembali->with('error', 'Pertemuan daring ini belum berlangsung.');
        }

        try {
            WebinarAttendance::create([
                'pelatihan_id' => $pelatihan->getKey(),
                'user_id' => $user->getKey(),
                'hadir_pada' => now(),
            ]);
        } catch (QueryException $e) {
            // Unique (pelatihan_id, user_id): tombol ditekan dua kali, bukan galat.
            if (! $pelatihan->sudahHadir($user)) {
                throw $e;
            }
        }

        return $kembali->with('success', 'Kehadiran Anda pada pertemuan daring ini sudah dicatat.');
    }
}
