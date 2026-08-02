<?php

namespace App\Services;

use App\Enums\ModuleProgressStatus;
use App\Models\Materi;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Support\Facades\DB;

/**
 * Gerbang belajar warga, berlapis dan berurutan:
 *
 *   1. Pelaksanaan pelatihan berstatus `terbuka`   ({@see isModuleAccessible})
 *   2. Modul prasyarat sudah diselesaikan          ({@see isModuleAccessible})
 *   3. Pre-test dikerjakan bila diaktifkan         ({@see butuhPretest})
 *   4. Materi dibaca berurutan                     ({@see isMateriAccessible})
 *   5. Evaluasi Kegiatan sebagai penutup
 */
class SlcProgressService
{
    public function isModuleAccessible(User $user, Module $module): bool
    {
        if ($user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])) {
            return true;
        }
        $module->loadMissing('pelatihan');

        if (! $module->pelatihan || ! $module->pelatihan->dapatDimasuki()) {
            return false;
        }

        if (! $module->prasyarat_module_id) {
            return true;
        }

        // Prasyarat yang sudah dihapus (soft-delete) tidak boleh mengunci warga selamanya.
        if (! $module->prerequisite()->exists()) {
            return true;
        }

        return UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $module->prasyarat_module_id)
            ->where('status', ModuleProgressStatus::Completed)
            ->exists();
    }

    /**
     * Pre-test WAJIB dikerjakan dulu? Tanpa syarat nilai: begitu satu percobaan
     * tercatat, gerbang ini terbuka selamanya (pre-test hanya sekali percobaan).
     */
    public function butuhPretest(User $user, Module $module): bool
    {
        $pretest = $module->pretest()->first();

        if (! $pretest || ! $pretest->isPlayable()) {
            return false;
        }

        return ! $pretest->sudahDikerjakan($user);
    }

    /**
     * Status modul tanpa query tambahan: pakai data yang sudah di-eager-load.
     * `$completedModuleIds` = daftar module_id yang sudah diselesaikan user (diambil sekali).
     *
     * @param  array<int, int>  $completedModuleIds
     */
    public function getModuleStatusUsing(Module $module, ?UserModuleProgress $progress, array $completedModuleIds): string
    {
        if (! $module->pelatihan || ! $module->pelatihan->dapatDimasuki()) {
            return 'locked';
        }

        // `$module->prerequisite` null bila prasyarat sudah dihapus → tidak mengunci.
        if ($module->prasyarat_module_id
            && $module->prerequisite
            && ! in_array($module->prasyarat_module_id, $completedModuleIds, true)) {
            return 'locked';
        }

        return match ($progress?->status) {
            ModuleProgressStatus::Completed => 'completed',
            ModuleProgressStatus::InProgress => 'in_progress',
            default => 'available',
        };
    }

    public function isModuleCompleted(User $user, Module $module): bool
    {
        return UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', ModuleProgressStatus::Completed)
            ->exists();
    }

    public function getProgress(User $user, Module $module): ?UserModuleProgress
    {
        return UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();
    }

    /** Tandai warga mulai belajar secara idempotent, tanpa enrollment manual. */
    public function startModule(User $user, Module $module): UserModuleProgress
    {
        return UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['status' => ModuleProgressStatus::InProgress, 'halaman_selesai' => []],
        );
    }

    /**
     * Materi wajib dibaca berurutan: sebuah materi hanya boleh diakses bila SEMUA
     * materi sebelumnya (berdasar urutan) sudah ditandai selesai. Materi pertama
     * selalu terbuka; materi yang sudah selesai tetap bisa dibuka untuk ditinjau.
     *
     * @param  array<int, int>  $materiSelesai
     */
    public function isMateriAccessible(Module $module, Materi $materi, array $materiSelesai): bool
    {
        $ids = $module->materis->pluck('id')->all();
        $targetIdx = array_search($materi->id, $ids, true);

        if ($targetIdx === false) {
            return false;
        }

        for ($i = 0; $i < $targetIdx; $i++) {
            if (! in_array($ids[$i], $materiSelesai, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Materi pertama yang belum selesai (titik "lanjut belajar") — selalu dapat diakses
     * karena semua materi sebelumnya pasti sudah selesai. Null bila seluruh modul tuntas.
     *
     * @param  array<int, int>  $materiSelesai
     */
    public function firstIncompleteMateri(Module $module, array $materiSelesai): ?Materi
    {
        return $module->materis->first(fn (Materi $m) => ! in_array($m->id, $materiSelesai, true));
    }

    public function markMateriCompleted(User $user, Module $module, Materi $materi): void
    {
        $this->startModule($user, $module);

        // ID materi yang masih ada — dipakai untuk rekonsiliasi (buang ID hantu
        // sisa materi yang sudah dihapus admin) dan menghitung ulang penyelesaian.
        $validMateriIds = $module->materis()->pluck('id')->all();

        // Kunci baris progres agar aman dari race double-submit.
        DB::transaction(function () use ($user, $module, $materi, $validMateriIds): void {
            $progress = UserModuleProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->lockForUpdate()
                ->first();

            $materiSelesai = array_values(array_intersect(
                array_unique([...($progress->halaman_selesai ?? []), $materi->id]),
                $validMateriIds
            ));

            $allDone = count($validMateriIds) > 0 && count($materiSelesai) === count($validMateriIds);
            $wasCompleted = $progress->status === ModuleProgressStatus::Completed;

            $progress->update([
                'halaman_selesai' => $materiSelesai,
                'status' => $allDone || $wasCompleted
                    ? ModuleProgressStatus::Completed
                    : ModuleProgressStatus::InProgress,
                // Kelulusan lama tetap sah ketika admin menambah materi baru.
                'completed_at' => $allDone || $wasCompleted
                    ? ($progress->completed_at ?? now())
                    : null,
            ]);
        });
    }
}
