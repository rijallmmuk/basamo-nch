<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Support\PublicNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman verifikasi sertifikat, terbuka untuk umum.
 *
 * Yang ditampilkan hanya yang perlu untuk membuktikan keaslian: nama penerima,
 * pelatihan, nagari, dan tanggal terbit. Nomor yang tidak dikenali dijawab dengan
 * halaman "tidak ditemukan" biasa, bukan 404, supaya pemeriksa tahu ia sudah sampai
 * di tempat yang benar dan nomornyalah yang salah.
 */
class SertifikatVerifikasiController extends Controller
{
    public function __invoke(Request $request, string $nomor): View|RedirectResponse
    {
        $kanonik = Certificate::urlVerifikasiUntuk($nomor);

        // Satu sertifikat, satu alamat. Rute publik tidak terikat domain sehingga
        // halaman ini ikut terlayani di tiap subdomain nagari maupun di `www`;
        // semuanya dipulangkan ke domain utama, yang alamatnya tercetak di sertifikat.
        if (! $this->diDomainUtama($request)) {
            return redirect()->to($kanonik, 301);
        }

        $certificate = Certificate::query()
            ->with(['user.nagari', 'pelatihan.tema'])
            ->where('nomor_seri', mb_strtoupper(trim($nomor)))
            ->first();

        return view('public.sertifikat-verifikasi', [
            'nomor' => $nomor,
            'certificate' => $certificate,
        ]);
    }

    private function diDomainUtama(Request $request): bool
    {
        $hostInduk = mb_strtolower(
            (string) parse_url(PublicNavigation::indukUrl(), PHP_URL_HOST)
        );

        return $hostInduk === '' || mb_strtolower($request->getHost()) === $hostInduk;
    }
}
