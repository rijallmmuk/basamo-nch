<?php

namespace App\Notifications;

use App\Models\UmkmProfile;
use Illuminate\Notifications\Notification;

/**
 * Hasil tinjauan pengajuan akses UMKM oleh admin desa — dikirim ke warga pengaju.
 * Disetujui → arahkan ke "Produk Saya"; ditolak → arahkan kembali ke form pengajuan
 * (alasan tercantum, warga boleh memperbaiki lalu mengajukan ulang).
 */
class UmkmApplicationDecided extends Notification
{
    public function __construct(
        public UmkmProfile $profile,
        public bool $approved,
        public ?string $reason = null,
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
            'type' => 'umkm_application_decided',
            'title' => $this->approved ? 'Pengajuan UMKM disetujui' : 'Pengajuan UMKM ditolak',
            'body' => $this->approved
                ? "Lapak \"{$this->profile->nama_usaha}\" kini aktif — produkmu tampil di katalog. Selamat berjualan!"
                : "Pengajuan \"{$this->profile->nama_usaha}\" ditolak: {$this->reason} Silakan perbaiki lalu ajukan ulang.",
            'icon' => $this->approved ? 'heroicon-s-check-badge' : 'heroicon-s-x-circle',
            'url' => $this->approved ? route('portal.umkm.index') : route('portal.umkm.ajukan'),
        ];
    }
}
