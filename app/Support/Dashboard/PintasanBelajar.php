<?php

namespace App\Support\Dashboard;

use App\Models\Materi;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\SlcProgressService;
use Illuminate\Support\Collection;

/**
 * "Pintasan Belajar Terakhir" pada beranda portal: satu tombol yang membawa warga
 * tepat ke langkah berikutnya.
 *
 * SYARAT UTAMA: pintasan hanya ada bila warga PERNAH belajar. Warga yang baru
 * pertama masuk tidak punya "belajar terakhir" untuk dilanjutkan, dan menawarkan
 * modul acak sebagai "lanjutkan" hanya membingungkan. Beranda menyembunyikan
 * kartunya dan mengajak mulai dari daftar pelatihan.
 *
 * Tujuannya WAJIB sama dengan yang akan diizinkan gerbang belajar
 * ({@see SlcProgressService}). Kalau tidak, tombolnya berbohong: warga menekan
 * "Lanjutkan Belajar" lalu dipantulkan controller ke halaman lain. Karena itu
 * pre-test ikut diperiksa di sini, bukan hanya materi dan evaluasi.
 */
final class PintasanBelajar
{
    public const MATERI = 'materi';

    public const PRETEST = 'pretest';

    public const EVALUASI = 'evaluasi';

    public const MULAI = 'mulai';

    /** Seluruh modul yang terbuka sudah tuntas: tidak ada langkah berikutnya. */
    public const TUNTAS = 'tuntas';

    /** Belum pernah belajar: kartu pintasan tidak ditampilkan sama sekali. */
    public const TIDAK_ADA = 'tidak_ada';

    private function __construct(
        public readonly ?Module $module,
        public readonly ?Materi $materi,
        public readonly string $jenis,
    ) {}

    /**
     * @param  Collection<int, Module>  $modules  modul yang terlihat warga, sudah terurut tampil
     * @param  array<int, string>|Collection<int, string>  $statusMap  module_id => status modul
     * @param  Collection<int, bool>  $evaluasiPendingMap  module_id => evaluasi kegiatan belum lulus
     */
    public static function untuk(
        User $user,
        Collection $modules,
        array|Collection $statusMap,
        Collection $evaluasiPendingMap,
        SlcProgressService $progressService,
    ): self {
        // Riwayat belajar diambil dari SELURUH baris progres, bukan hanya yang
        // terbaru. Baris terbaru bisa menunjuk modul yang sudah dihapus pengajar
        // atau pelatihannya dikunci lagi; kalau hanya itu yang dilihat, warga yang
        // sebenarnya punya riwayat akan diperlakukan seperti belum pernah belajar.
        $riwayat = UserModuleProgress::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get(['id', 'module_id', 'halaman_selesai', 'updated_at']);

        if ($riwayat->isEmpty()) {
            return new self(null, null, self::TIDAK_ADA);
        }

        $status = fn (Module $module): string => (is_array($statusMap) ? ($statusMap[$module->id] ?? null) : $statusMap->get($module->id)) ?? 'available';
        $terkunci = fn (Module $module): bool => $status($module) === 'locked';
        $evaluasiTertunda = fn (Module $module): bool => (bool) $evaluasiPendingMap->get($module->id, false);

        // 1. Lanjutkan modul yang paling terakhir disentuh dan masih boleh dimasuki.
        $modulTerakhir = $riwayat
            ->map(fn (UserModuleProgress $progres): ?Module => $modules->firstWhere('id', $progres->module_id))
            ->filter()
            ->reject($terkunci)
            ->first();

        if ($modulTerakhir) {
            $langkah = self::langkahDalam($user, $modulTerakhir, $evaluasiTertunda($modulTerakhir), $progressService);

            if ($langkah !== null) {
                return $langkah;
            }
        }

        // 2. Modul terakhir sudah tuntas: ambil modul terbuka berikutnya yang belum tuntas.
        $modulBerikutnya = $modules
            ->reject($terkunci)
            ->first(fn (Module $module): bool => $status($module) !== 'completed' || $evaluasiTertunda($module));

        if (! $modulBerikutnya) {
            return new self(null, null, self::TUNTAS);
        }

        return self::langkahDalam($user, $modulBerikutnya, $evaluasiTertunda($modulBerikutnya), $progressService)
            ?? new self($modulBerikutnya, null, self::MULAI);
    }

    public function ada(): bool
    {
        return $this->module !== null;
    }

    /**
     * Langkah berikutnya DI DALAM satu modul, mengikuti urutan gerbang belajar:
     * pre-test, lalu materi berurutan, lalu Evaluasi Kegiatan. Null bila modul ini
     * sudah tak menyisakan apa pun.
     */
    private static function langkahDalam(
        User $user,
        Module $module,
        bool $evaluasiTertunda,
        SlcProgressService $progressService,
    ): ?self {
        if ($progressService->butuhPretest($user, $module)) {
            return new self($module, null, self::PRETEST);
        }

        $materiSelesai = $module->progress->first()?->halaman_selesai ?? [];
        $materiBerikutnya = $progressService->firstIncompleteMateri($module, $materiSelesai);

        if ($materiBerikutnya) {
            return new self($module, $materiBerikutnya, self::MATERI);
        }

        if ($evaluasiTertunda) {
            return new self($module, null, self::EVALUASI);
        }

        return null;
    }
}
