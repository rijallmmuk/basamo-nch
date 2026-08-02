<?php

namespace App\Notifications;

use App\Models\Module;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

// ShouldQueue: fan-out ke banyak warga (modul global bisa ribuan) → WAJIB antre + worker
// (`php artisan queue:work`). Tanpa worker, notif ini tak terkirim.
class NewModulePublished extends Notification implements ShouldQueue
{
    use Queueable;

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
            'body' => $this->module->judul,
            'icon' => 'heroicon-s-book-open',
            // URL relatif (host-agnostik) — notif ter-antre di-generate di worker; URL
            // absolut akan memakai APP_URL & bisa pindah host saat diklik → hilang sesi.
            'url' => route('portal.modules.show', $this->module, absolute: false),
        ];
    }
}
