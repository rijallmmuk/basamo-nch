<?php

namespace App\Filament\Resources\UmkmProducts\Pages;

use App\Filament\Resources\UmkmProducts\Schemas\UmkmProductForm;
use App\Filament\Resources\UmkmProducts\Support\UmkmProductActions;
use App\Filament\Resources\UmkmProducts\UmkmProductResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Detail produk UMKM baca-saja. Pintu supervisi DPMD (ditolak moderasi oleh Gate::before).
 * Pemilik dan operator mendapat tombol Edit serta Hapus Produk langsung di sini.
 */
class ViewUmkmProduct extends ViewRecord
{
    protected static string $resource = UmkmProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Produk')
                ->color('warning')
                ->modalHeading('Ubah Produk')
                ->modalSubmitActionLabel('Simpan Perubahan')
                ->schema(UmkmProductForm::components())
                ->using(function (UmkmProduct $record, array $data): UmkmProduct {
                    if (UmkmProfileResource::isSelfService()) {
                        return app(UmkmService::class)->updateProduct($record, $data);
                    }

                    $record->update($data);

                    return $record;
                }),
            UmkmProductActions::delete(),
        ];
    }
}
