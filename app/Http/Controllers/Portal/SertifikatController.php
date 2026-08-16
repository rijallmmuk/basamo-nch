<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModeSertifikat;
use App\Http\Controllers\Controller;
use App\Models\Pelatihan;
use App\Services\SertifikatService;
use App\Support\SertifikatPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SertifikatController extends Controller
{
    public function __construct(private readonly SertifikatService $sertifikat) {}

    /**
     * Ambil sertifikat pelatihan.
     *
     * Kelayakan diperiksa ulang di sini, bukan dipercayakan pada tombol yang tampil:
     * alamatnya dapat diketik langsung.
     */
    public function unduh(Pelatihan $pelatihan): Response|RedirectResponse
    {
        $user = auth()->user();

        abort_unless(
            Pelatihan::query()
                ->accessibleToWarga($user)
                ->whereKey($pelatihan->getKey())
                ->exists(),
            404,
        );

        if (! $this->sertifikat->berhak($user, $pelatihan)) {
            return redirect()
                ->route('portal.pelatihan.show', $pelatihan)
                ->with('error', $this->sertifikat->alasanBelumBerhak($user, $pelatihan)
                    ?? 'Sertifikat belum tersedia untuk pelatihan ini.');
        }

        $certificate = $this->sertifikat->terbitkan($user, $pelatihan);

        if ($pelatihan->sertifikat_mode === ModeSertifikat::Unggah) {
            return $this->berkasPenyelenggara($pelatihan);
        }

        $pdf = SertifikatPdf::buat(
            $certificate,
            $pelatihan->loadMissing('tema', 'nagari'),
            $user->loadMissing('nagari'),
        );

        return $pdf->download('Sertifikat-'.$certificate->nomor_seri.'.pdf');
    }

    /** Berkas siap pakai milik penyelenggara, sama untuk seluruh peserta. */
    private function berkasPenyelenggara(Pelatihan $pelatihan): Response
    {
        $media = $pelatihan->getFirstMedia('sertifikat');

        abort_if($media === null, 404);

        $disk = Storage::disk($media->disk);

        abort_unless($disk->exists($media->getPathRelativeToRoot()), 404);

        $nama = 'Sertifikat-'.str($pelatihan->temaNama())->slug().'.'
            .pathinfo($media->file_name, PATHINFO_EXTENSION);

        return $disk->download($media->getPathRelativeToRoot(), $nama);
    }
}
