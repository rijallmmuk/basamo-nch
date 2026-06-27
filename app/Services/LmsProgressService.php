<?php

namespace App\Services;

use App\Enums\ModuleProgressStatus;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Support\Facades\DB;

class LmsProgressService
{
    public function __construct(private readonly LmsPointService $pointService) {}

    public function isModuleAccessible(User $user, Module $module): bool
    {
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
     * Status modul tanpa query tambahan: pakai data yang sudah di-eager-load.
     * `$completedModuleIds` = daftar module_id yang sudah diselesaikan user (diambil sekali).
     *
     * @param  array<int, int>  $completedModuleIds
     */
    public function getModuleStatusUsing(Module $module, ?UserModuleProgress $progress, array $completedModuleIds): string
    {
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

    /**
     * Materi wajib dibaca berurutan: sebuah halaman hanya boleh diakses bila SEMUA
     * halaman sebelumnya (berdasar urutan) sudah ditandai selesai. Halaman pertama
     * selalu terbuka; halaman yang sudah selesai tetap bisa dibuka untuk ditinjau.
     *
     * @param  array<int, int>  $pagesCompleted
     */
    public function isPageAccessible(Module $module, ModulePage $page, array $pagesCompleted): bool
    {
        $ids = $module->pages->pluck('id')->all();
        $targetIdx = array_search($page->id, $ids, true);

        if ($targetIdx === false) {
            return false;
        }

        for ($i = 0; $i < $targetIdx; $i++) {
            if (! in_array($ids[$i], $pagesCompleted, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Halaman pertama yang belum selesai (titik "lanjut belajar") — selalu dapat diakses
     * karena semua halaman sebelumnya pasti sudah selesai. Null bila seluruh modul tuntas.
     *
     * @param  array<int, int>  $pagesCompleted
     */
    public function firstIncompletePage(Module $module, array $pagesCompleted): ?ModulePage
    {
        return $module->pages->first(fn (ModulePage $p) => ! in_array($p->id, $pagesCompleted, true));
    }

    public function getModuleStatus(User $user, Module $module): string
    {
        if (! $this->isModuleAccessible($user, $module)) {
            return 'locked';
        }

        $progress = $this->getProgress($user, $module);

        return match ($progress?->status) {
            ModuleProgressStatus::Completed => 'completed',
            ModuleProgressStatus::InProgress => 'in_progress',
            default => 'available',
        };
    }

    public function markPageCompleted(User $user, Module $module, ModulePage $page): void
    {
        UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['status' => ModuleProgressStatus::InProgress, 'halaman_selesai' => []]
        );

        // ID halaman yang masih ada — dipakai untuk rekonsiliasi (buang ID hantu
        // sisa halaman yang sudah dihapus admin) dan menghitung ulang penyelesaian.
        $validPageIds = $module->pages()->pluck('id')->all();

        // Kunci baris progres agar aman dari race double-submit.
        $isAllDone = DB::transaction(function () use ($user, $module, $page, $validPageIds) {
            $progress = UserModuleProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->lockForUpdate()
                ->first();

            $pagesCompleted = array_values(array_intersect(
                array_unique([...($progress->halaman_selesai ?? []), $page->id]),
                $validPageIds
            ));

            $allDone = count($validPageIds) > 0 && count($pagesCompleted) === count($validPageIds);

            $progress->update([
                'halaman_selesai' => $pagesCompleted,
                'status' => $allDone ? ModuleProgressStatus::Completed : ModuleProgressStatus::InProgress,
                // Pertahankan waktu selesai pertama; jangan di-bump ulang.
                'completed_at' => $allDone ? ($progress->completed_at ?? now()) : null,
            ]);

            return $allDone;
        });

        // XP modul selesai (idempotent — hanya sekali per modul via xp_logs).
        if ($isAllDone) {
            $this->pointService->awardModuleCompletion($user, $module);
        }
    }
}
