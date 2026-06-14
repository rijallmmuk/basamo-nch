<?php

namespace App\Notifications;

use App\Models\Module;
use Illuminate\Notifications\Notification;

class NewModulePublished extends Notification
{
    public function __construct(public Module $module) {}

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
            'type' => 'new_module',
            'title' => 'Modul baru tersedia',
            'body' => $this->module->title,
            'icon' => 'heroicon-s-book-open',
            'url' => route('portal.modules.show', $this->module),
        ];
    }
}
