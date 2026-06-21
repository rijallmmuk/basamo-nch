<?php

namespace App\Notifications;

use App\Enums\UmkmProductStatus;
use App\Models\UmkmProduct;
use Illuminate\Notifications\Notification;

class UmkmProductVerified extends Notification
{
    public function __construct(public UmkmProduct $product) {}

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
        $approved = $this->product->status === UmkmProductStatus::Approved;

        return [
            'type' => 'umkm_product_verified',
            'title' => $approved ? 'Produk disetujui' : 'Produk ditolak',
            'body' => $approved
                ? $this->product->nama_produk.' kini tampil di katalog.'
                : $this->product->nama_produk.' ditolak: '.$this->product->rejection_reason,
            'icon' => $approved ? 'heroicon-s-check-badge' : 'heroicon-s-x-circle',
            'url' => route('portal.umkm.index'),
        ];
    }
}
