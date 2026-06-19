<?php

namespace App\Observers;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\NewQuizPublished;
use Illuminate\Support\Facades\Notification;

class QuizObserver
{
    /**
     * Judul kuis opsional: bila kosong, pakai "Kuis: {judul modul}".
     * (1 modul = 1 kuis, jadi judul terpisah jarang berguna.)
     */
    public function saving(Quiz $quiz): void
    {
        if (filled($quiz->title)) {
            return;
        }

        $module = $quiz->module ?? Module::find($quiz->module_id);

        if ($module) {
            $quiz->title = "Kuis: {$module->title}";
        }
    }

    public function created(Quiz $quiz): void
    {
        $module = $quiz->module;

        // Hanya beri tahu bila modulnya sudah published (warga bisa mengaksesnya).
        if (! $module || $module->status !== 'published') {
            return;
        }

        $query = User::query()
            ->whereIn('role', ['warga', 'umkm_owner'])
            ->where('status', 'active');

        if ($module->nagari_id !== null) {
            $query->where('nagari_id', $module->nagari_id);
        }

        $users = $query->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new NewQuizPublished($quiz));
        }
    }
}
