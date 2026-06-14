<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Notifications\Notification;

class NewQuizPublished extends Notification
{
    public function __construct(public Quiz $quiz) {}

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
            'type' => 'new_quiz',
            'title' => 'Kuis baru tersedia',
            'body' => 'Modul: '.$this->quiz->module->title,
            'icon' => 'heroicon-s-clipboard-document-list',
            'url' => route('portal.modules.show', $this->quiz->module),
        ];
    }
}
