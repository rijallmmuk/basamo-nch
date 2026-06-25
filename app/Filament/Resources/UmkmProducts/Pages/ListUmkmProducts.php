<?php

namespace App\Filament\Resources\UmkmProducts\Pages;

use App\Filament\Resources\Desas\DesaResource;
use App\Filament\Resources\UmkmProducts\UmkmProductResource;
use App\Support\DesaContext;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListUmkmProducts extends ListRecords
{
    protected static string $resource = UmkmProductResource::class;

    /** Saat super admin mengelola desa tertentu, perjelas konteksnya di subjudul. */
    public function getSubheading(): ?string
    {
        $desa = (auth()->user()?->isSuperAdmin() ?? false) ? DesaContext::desa() : null;

        return $desa ? 'Verifikasi produk — '.$desa->nama_lengkap : null;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Super admin dalam konteks desa → tombol keluar (kembali ke daftar Desa).
        if ((auth()->user()?->isSuperAdmin() ?? false) && DesaContext::id() !== null) {
            $actions[] = Action::make('kembaliKeDesa')
                ->label('Kembali ke Desa')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->action(function () {
                    DesaContext::clear();

                    return redirect(DesaResource::getUrl('index'));
                });
        }

        return $actions;
    }
}
