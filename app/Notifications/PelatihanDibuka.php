<?php

namespace App\Notifications;

use App\Models\Pelatihan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Diumumkan ke warga nagari sasaran saat pengelola MEMBUKA pelatihan (status menjadi
 * terbuka). ShouldQueue: fan-out ke banyak warga → WAJIB queue worker.
 */
class PelatihanDibuka extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Pelatihan $pelatihan) {}

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
            'type' => 'pelatihan_dibuka',
            'title' => 'Pelatihan dibuka',
            'body' => $this->pelatihan->namaTampil().' sudah dibuka. Ayo mulai belajar!',
            'icon' => 'heroicon-s-academic-cap',
            'url' => route('portal.pelatihan.index', absolute: false),
        ];
    }
}
