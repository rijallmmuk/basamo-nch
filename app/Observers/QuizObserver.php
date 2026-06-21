<?php

namespace App\Observers;

use App\Models\Quiz;
use App\Models\User;
use App\Notifications\NewQuizPublished;
use Illuminate\Support\Facades\Notification;

class QuizObserver
{
    public function created(Quiz $quiz): void
    {
        $module = $quiz->module;

        // Hanya beri tahu bila modulnya sudah published (warga bisa mengaksesnya).
        if (! $module || $module->status !== 'published') {
            return;
        }

        $query = User::query()
            ->where('role', 'warga')
            ->where('status', 'active');

        if ($module->desa_id !== null) {
            $query->where('desa_id', $module->desa_id);
        }

        // Kirim bertahap (notifikasi sudah ShouldQueue) agar tak memuat seluruh
        // warga ke memori untuk modul global.
        $query->chunkById(500, function ($users) use ($quiz) {
            Notification::send($users, new NewQuizPublished($quiz));
        });
    }
}
