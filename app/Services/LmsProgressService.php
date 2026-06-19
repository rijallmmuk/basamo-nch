<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModulePage;
use App\Models\User;
use App\Models\UserModuleProgress;

class LmsProgressService
{
    public function __construct(private readonly LmsPointService $pointService) {}

    public function isModuleAccessible(User $user, Module $module): bool
    {
        if (! $module->prerequisite_module_id) {
            return true;
        }

        return UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $module->prerequisite_module_id)
            ->where('status', 'completed')
            ->exists();
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
            'completed' => 'completed',
            'in_progress' => 'in_progress',
            default => 'available',
        };
    }

    public function markPageCompleted(User $user, Module $module, ModulePage $page): void
    {
        $progress = UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['status' => 'in_progress', 'pages_completed' => []]
        );

        $pagesCompleted = $progress->pages_completed ?? [];

        if (in_array($page->id, $pagesCompleted)) {
            return;
        }

        $pagesCompleted[] = $page->id;
        $totalPages = $module->pages()->count();
        $isAllDone = count($pagesCompleted) >= $totalPages;

        $progress->update([
            'pages_completed' => $pagesCompleted,
            'status' => $isAllDone ? 'completed' : 'in_progress',
            'completed_at' => $isAllDone ? now() : null,
        ]);

        // XP modul selesai (idempotent — hanya sekali per modul).
        if ($isAllDone) {
            $this->pointService->awardModuleCompletion($user, $module);
        }
    }
}
