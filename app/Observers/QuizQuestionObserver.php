<?php

namespace App\Observers;

use App\Enums\ActiveStatus;
use App\Enums\ModuleStatus;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Notifications\NewQuizPublished;
use Illuminate\Support\Facades\Notification;

class QuizQuestionObserver
{
    /**
     * Notifikasi "kuis baru" dikirim saat SOAL PERTAMA dibuat — bukan saat baris
     * kuis dibuat — karena kuis tanpa soal belum bisa dikerjakan warga (portal
     * menolaknya), sehingga notifikasi lebih awal hanya menyesatkan.
     */
    public function created(QuizQuestion $question): void
    {
        $quiz = $question->quiz;

        // Hanya soal pertama; kuis terarsip (soft-delete) otomatis lolos karena
        // relasi quiz mengembalikan null.
        if (! $quiz || $quiz->questions()->count() !== 1) {
            return;
        }

        $module = $quiz->module;

        // Hanya bila modulnya sudah published (warga bisa mengaksesnya).
        if (! $module || $module->status !== ModuleStatus::Published) {
            return;
        }

        $query = User::query()
            ->where('role', 'warga')
            ->where('status', ActiveStatus::Active);

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
