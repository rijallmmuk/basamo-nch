<?php

namespace App\Notifications;

use App\Models\Evaluasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEvaluasiPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Evaluasi $evaluasi) {}

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
            'title' => $this->evaluasi->jenis->getLabel().' baru tersedia',
            'body' => 'Modul: '.$this->evaluasi->module->judul,
            'icon' => 'heroicon-s-clipboard-document-list',
            // URL relatif (host-agnostik) — lihat catatan di NewModulePublished.
            'url' => route('portal.modules.show', $this->evaluasi->module, absolute: false),
        ];
    }
}
