<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Models\XpLog;
use Illuminate\Support\Facades\DB;

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
     * Catat XP sekali saja (idempotent via UNIQUE(user, sumber, sumber_id))
     * lalu tambahkan ke total_xp hanya bila baris baru benar-benar dibuat.
     *
     * `createOrFirst` (bukan `firstOrCreate`) → aman di bawah konkurensi: bila dua
     * award bersamaan untuk kunci sama, pelanggaran UNIQUE ditangkap & baris yang
     * sudah ada dikembalikan (tanpa 500, tanpa XP ganda).
     */
    private function award(User $user, string $source, int $sourceId, int $amount): void
    {
        // Satu transaksi: ledger & total_xp tak boleh drift bila gagal di tengah.
        DB::transaction(function () use ($user, $source, $sourceId, $amount) {
            $log = XpLog::createOrFirst(
                ['user_id' => $user->id, 'sumber' => $source, 'sumber_id' => $sourceId],
                ['desa_id' => $user->desa_id, 'jumlah' => $amount],
            );

            if ($log->wasRecentlyCreated) {
                $user->increment('total_xp', $amount);
            }
        });
    }
}
