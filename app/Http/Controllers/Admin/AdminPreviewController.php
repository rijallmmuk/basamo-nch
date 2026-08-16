<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Materi;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\TemaPelatihan;
use App\Models\User;
use App\Support\SertifikatPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Contoh sertifikat, ditampilkan di dalam peramban dan tidak diunduh.
     *
     * Temanya datang dari form yang sedang diisi, jadi pengajar dapat menilai wujud
     * sertifikatnya sebelum pelatihannya disimpan. Berkasnya dirakit lewat perakit yang
     * sama dengan sertifikat asli; yang membedakan hanya nomor seri contoh dan penanda
     * di badan sertifikat, sehingga halaman verifikasi menjawab "tidak ditemukan".
     */
    public function contohSertifikat(Request $request): Response
    {
        $this->checkAdmin();

        $tema = trim((string) $request->query('tema', ''));
        $tema = $tema !== '' ? mb_substr($tema, 0, 200) : 'Tema Pelatihan';

        $pelatihan = new Pelatihan;
        $pelatihan->setRelation('tema', new TemaPelatihan(['nama' => $tema]));

        // Nama contoh, bukan nama pengajar yang membuka: sertifikat tidak pernah
        // menyebut pembuatnya, dan memakai namanya bisa disalahpahami.
        $warga = new User(['name' => 'Nama Lengkap Warga Penerima']);
        $warga->setRelation('nagari', auth()->user()?->nagari
            ?? Nagari::query()->orderBy('nama')->first());

        $certificate = new Certificate([
            'nomor_seri' => 'NCH-'.now()->year.'-CONTOH00',
            'diterbitkan_pada' => now(),
        ]);

        return SertifikatPdf::buat($certificate, $pelatihan, $warga, contoh: true)
            ->stream('Contoh-Sertifikat.pdf');
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
