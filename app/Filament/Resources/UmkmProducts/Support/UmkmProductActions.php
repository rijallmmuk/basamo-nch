<?php

namespace App\Filament\Resources\UmkmProducts\Support;

use App\Models\UmkmProduct;
use Filament\Actions\DeleteAction;

/**
 * Aksi produk yang dipakai bersama oleh tabel produk, relasi produk pada lapak,
 * dan halaman detail produk, supaya kata-katanya persis sama di ketiganya.
 *
 * Produk TIDAK punya arsip: menghapus berarti menghapus permanen beserta seluruh
 * fotonya. Karena itu konfirmasinya menyebutkan akibatnya secara terang.
 */
class UmkmProductActions
{
    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Hapus Produk')
            ->modalHeading(fn (UmkmProduct $record): string => "Hapus produk \"{$record->nama_produk}\"?")
            ->modalDescription('Produk dan seluruh fotonya dihapus permanen dan langsung hilang dari etalase. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, hapus permanen')
            ->successNotificationTitle('Produk dihapus permanen');
    }
}
