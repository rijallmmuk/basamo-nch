<?php

namespace App\Notifications;

use App\Models\Discussion;
use Illuminate\Notifications\Notification;

class DiscussionReplied extends Notification
{
    /**
     * @param  Discussion  $thread  Pertanyaan (top-level) yang dibalas — penerima notif = penulisnya.
     * @param  string  $replierName  Nama pembalas (admin atau warga lain).
     */
    public function __construct(
        public Discussion $thread,
        public string $replierName,
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
            'type' => 'discussion_reply',
            'title' => 'Ada balasan untuk pertanyaanmu',
            'body' => $this->replierName.' membalas di modul '.$this->thread->module->judul,
            'icon' => 'heroicon-s-chat-bubble-left-right',
            'url' => route('portal.modules.discuss.show', [$this->thread->module, $this->thread]),
        ];
    }
}
