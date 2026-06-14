<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Models\XpLog;

class LmsPointService
{
    /** XP per pencapaian (masing-masing diberi sekali per modul). */
    public const MODULE_XP = 50;

    public const QUIZ_XP = 100;

    public const DISCUSSION_XP = 20;

    public function awardModuleCompletion(User $user, Module $module): void
    {
        $this->award($user, 'module', $module->id, self::MODULE_XP);
    }

    public function awardQuizPass(User $user, Quiz $quiz): void
    {
        $this->award($user, 'quiz', $quiz->id, self::QUIZ_XP);
    }

    /**
     * Partisipasi diskusi: diberi sekali per modul (posting pertama di modul).
     */
    public function awardDiscussionParticipation(User $user, Module $module): void
    {
        $this->award($user, 'discussion', $module->id, self::DISCUSSION_XP);
    }

    /**
     * Catat XP sekali saja (idempotent via UNIQUE(user, source, source_id))
     * lalu tambahkan ke total_points hanya bila baris baru benar-benar dibuat.
     */
    private function award(User $user, string $source, int $sourceId, int $amount): void
    {
        $log = XpLog::firstOrCreate(
            ['user_id' => $user->id, 'source' => $source, 'source_id' => $sourceId],
            ['nagari_id' => $user->nagari_id, 'amount' => $amount],
        );

        if ($log->wasRecentlyCreated) {
            $user->increment('total_points', $amount);
        }
    }
}
