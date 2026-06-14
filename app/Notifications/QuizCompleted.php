<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Notifications\Notification;

class QuizCompleted extends Notification
{
    public function __construct(
        public Quiz $quiz,
        public int $score,
        public bool $passed,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quiz_result',
            'title' => $this->passed ? 'Selamat, kamu lulus kuis!' : 'Hasil kuis: belum lulus',
            'body' => $this->quiz->title.' — nilai '.$this->score,
            'icon' => $this->passed ? 'heroicon-s-check-badge' : 'heroicon-s-x-circle',
            'url' => route('portal.modules.show', $this->quiz->module),
        ];
    }
}
