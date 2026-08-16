<?php

namespace App\Services;

use App\Enums\JenisEvaluasi;
use App\Enums\ModeSertifikat;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Models\Certificate;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Database\QueryException;

/**
 * Kelayakan dan penerbitan sertifikat pelatihan.
 *
 * SYARATNYA DUA, dan keduanya harus terpenuhi:
 *   1. seluruh modul pelatihan yang terlihat warga berstatus selesai;
 *   2. tiap modul yang punya Evaluasi Kegiatan siap pakai sudah DILULUSI.
 *
 * Syarat kedua sengaja lebih ketat daripada definisi "modul selesai" yang berlaku di
 * portal, yang cukup menandai seluruh materi terbaca. Sertifikat adalah pernyataan
 * ke pihak luar, jadi ia tidak boleh terbit hanya atas dasar tombol yang ditekan.
 * Modul tanpa evaluasi tetap lolos: keberadaan evaluasi adalah pilihan pengajar.
 */
class SertifikatService
{
    public function __construct(private readonly SlcProgressService $progressService) {}

    /**
     * Sertifikat yang PERNAH terbit untuk warga ini, apa pun keadaan pelatihannya kini.
     *
     * Penerbitan adalah peristiwa yang sudah lewat. Pengajar dapat menambah modul kapan
     * saja sesudahnya, dan itu tidak boleh mencabut dokumen yang telanjur diperoleh:
     * nomornya sudah beredar dan halaman verifikasi publik tetap menyatakannya sah.
     */
    public function milik(User $user, Pelatihan $pelatihan): ?Certificate
    {
        return Certificate::where('pelatihan_id', $pelatihan->getKey())
            ->where('user_id', $user->getKey())
            ->first();
    }

    /** Pelatihan ini memberi sertifikat dan warga sudah berhak mengambilnya? */
    public function berhak(User $user, Pelatihan $pelatihan): bool
    {
        /* Mode unggah tidak menyimpan berkas per warga: yang diambil selalu berkas
           penyelenggara. Tanpa berkas itu tidak ada yang bisa disajikan, bahkan kepada
           warga yang sertifikatnya sudah terbit. Karena itu penjaga ini didahulukan. */
        if ($pelatihan->sertifikat_mode === ModeSertifikat::Unggah
            && $pelatihan->getFirstMedia('sertifikat') === null) {
            return false;
        }

        // Sudah pernah terbit: syarat di bawah tidak diperiksa ulang. Lihat {@see milik}.
        if ($this->milik($user, $pelatihan) !== null) {
            return true;
        }

        if (! $pelatihan->sertifikat_mode->memberiSertifikat()) {
            return false;
        }

        /* Webinar: satu-satunya buktinya adalah catatan kehadiran, sebab tidak ada modul
           maupun materi yang bisa dituntaskan. Bila pelatihan membawa keduanya, webinar
           DAN modul, maka dua-duanya harus terpenuhi. */
        if ($pelatihan->adalahWebinar() && ! $pelatihan->sudahHadir($user)) {
            return false;
        }

        $modules = $pelatihan->modules()->visibleToWarga($user)->with('materis')->get();

        if ($modules->isEmpty()) {
            return $pelatihan->adalahWebinar();
        }

        return $this->seluruhModulSelesai($user, $modules)
            && $this->seluruhEvaluasiLulus($user, $modules);
    }

    /**
     * Terbitkan sekali, lalu kembalikan baris yang sama pada pengambilan berikutnya.
     *
     * Idempoten sampai ke lapisan basis data: unique `(pelatihan_id, user_id)` menjadi
     * penjaga terakhir bila dua permintaan berbarengan lolos dari pemeriksaan di sini.
     */
    public function terbitkan(User $user, Pelatihan $pelatihan): Certificate
    {
        $ada = Certificate::where('pelatihan_id', $pelatihan->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($ada) {
            return $ada;
        }

        try {
            return Certificate::create([
                'pelatihan_id' => $pelatihan->getKey(),
                'user_id' => $user->getKey(),
                'nomor_seri' => $this->nomorSeriBaru(),
                'diterbitkan_pada' => now(),
            ]);
        } catch (QueryException $e) {
            $ada = Certificate::where('pelatihan_id', $pelatihan->getKey())
                ->where('user_id', $user->getKey())
                ->first();

            if ($ada) {
                return $ada;
            }

            throw $e;
        }
    }

    /**
     * Nomor seri yang tidak dapat ditebak.
     *
     * Halaman verifikasi terbuka untuk umum, jadi nomor berurutan akan membuat siapa
     * pun dapat menyusuri seluruh penerima beserta namanya hanya dengan menghitung.
     * Huruf I, L, O, dan U dibuang supaya tidak tertukar saat dibaca dari kertas.
     */
    private function nomorSeriBaru(): string
    {
        $abjad = 'ABCDEFGHJKMNPQRSTVWXYZ0123456789';

        do {
            $acak = '';

            for ($i = 0; $i < 8; $i++) {
                $acak .= $abjad[random_int(0, strlen($abjad) - 1)];
            }

            $nomor = 'NCH-'.now()->year.'-'.$acak;
        } while (Certificate::where('nomor_seri', $nomor)->exists());

        return $nomor;
    }

    /** @param \Illuminate\Support\Collection<int, Module> $modules */
    private function seluruhModulSelesai(User $user, $modules): bool
    {
        $selesai = UserModuleProgress::where('user_id', $user->getKey())
            ->whereIn('module_id', $modules->modelKeys())
            ->where('status', ModuleProgressStatus::Completed)
            ->pluck('module_id');

        return $selesai->count() === $modules->count();
    }

    /** @param \Illuminate\Support\Collection<int, Module> $modules */
    private function seluruhEvaluasiLulus(User $user, $modules): bool
    {
        $evaluasiWajib = $modules
            ->flatMap(fn (Module $module) => $module->evaluasiKegiatan()->ready()->pluck('evaluasis.id'))
            ->unique();

        if ($evaluasiWajib->isEmpty()) {
            return true;
        }

        $lulus = EvaluasiPercobaan::where('user_id', $user->getKey())
            ->whereIn('evaluasi_id', $evaluasiWajib)
            ->where('status', StatusPercobaan::Passed)
            ->pluck('evaluasi_id')
            ->unique();

        return $lulus->count() === $evaluasiWajib->count();
    }

    /** Alasan warga belum berhak, untuk ditampilkan apa adanya di portal. */
    public function alasanBelumBerhak(User $user, Pelatihan $pelatihan): ?string
    {
        if (! $pelatihan->sertifikat_mode->memberiSertifikat()) {
            return null;
        }

        if ($pelatihan->sertifikat_mode === ModeSertifikat::Unggah
            && $pelatihan->getFirstMedia('sertifikat') === null) {
            return 'Berkas sertifikat belum diunggah pengelola pelatihan.';
        }

        if ($pelatihan->adalahWebinar() && ! $pelatihan->sudahHadir($user)) {
            return $pelatihan->pertemuanSudahMulai()
                ? 'Tandai dulu bahwa Anda mengikuti pertemuan daring ini.'
                : 'Sertifikat tersedia setelah pertemuan daringnya berlangsung.';
        }

        $modules = $pelatihan->modules()->visibleToWarga($user)->with('materis')->get();

        if ($modules->isEmpty()) {
            return $pelatihan->adalahWebinar() ? null : 'Pelatihan ini belum berisi modul.';
        }

        if (! $this->seluruhModulSelesai($user, $modules)) {
            return 'Selesaikan seluruh modul pelatihan ini lebih dulu.';
        }

        if (! $this->seluruhEvaluasiLulus($user, $modules)) {
            return 'Lulus dulu seluruh Evaluasi Kegiatan pada pelatihan ini.';
        }

        return null;
    }

    /** Jenis evaluasi yang mengikat sertifikat, dipakai juga oleh test. */
    public function jenisEvaluasiWajib(): JenisEvaluasi
    {
        return JenisEvaluasi::Kegiatan;
    }
}
