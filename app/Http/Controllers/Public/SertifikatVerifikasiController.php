<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\View\View;

/**
 * Halaman verifikasi sertifikat, terbuka untuk umum.
 *
 * Yang ditampilkan hanya yang memang perlu untuk membuktikan keaslian: nama penerima,
 * pelatihan, nagari, dan tanggal terbit. Nomor yang tidak dikenali dijawab dengan
 * halaman "tidak ditemukan" biasa, bukan 404, supaya pemeriksa tahu ia sudah sampai
 * di tempat yang benar dan nomornyalah yang salah.
 */
class SertifikatVerifikasiController extends Controller
{
    public function __invoke(string $nomor): View
    {
        $certificate = Certificate::query()
            ->with(['user.nagari', 'pelatihan.tema'])
            ->where('nomor_seri', mb_strtoupper(trim($nomor)))
            ->first();

        return view('public.sertifikat-verifikasi', [
            'nomor' => $nomor,
            'certificate' => $certificate,
        ]);
    }
}
