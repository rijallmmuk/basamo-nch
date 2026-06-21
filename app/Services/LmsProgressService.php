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
        if (! $module->prerequisite_module_id) {
            return true;
        }

        // Prasyarat yang sudah dihapus (soft-delete) tidak boleh mengunci warga selamanya.
        if (! $module->prerequisite()->exists()) {
            return true;
        }

        return UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $module->prerequisite_module_id)
            ->where('status', 'completed')
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
        if ($module->prerequisite_module_id
            && $module->prerequisite
            && ! in_array($module->prerequisite_module_id, $completedModuleIds, true)) {
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
            ->where('status', 'completed')
            ->exists();
    }

    public function getProgress(User $user, Module $module): ?UserModuleProgress
    {
        return UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();
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
            ['status' => 'in_progress', 'pages_completed' => []]
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
                array_unique([...($progress->pages_completed ?? []), $page->id]),
                $validPageIds
            ));

            $allDone = count($validPageIds) > 0 && count($pagesCompleted) === count($validPageIds);

            $progress->update([
                'pages_completed' => $pagesCompleted,
                'status' => $allDone ? 'completed' : 'in_progress',
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
