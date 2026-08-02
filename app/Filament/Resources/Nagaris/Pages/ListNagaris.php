<?php

namespace App\Filament\Resources\Nagaris\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Nagaris\NagariResource;
use App\Support\NagariContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNagaris extends ListRecords
{
    protected static string $resource = NagariResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        // Blokir operator dari halaman ini secara eksplisit (hanya superadmin & dpmd yang boleh).
        // Ini perlu karena NagariResource::canAccess() di-bypass agar menu muncul untuk operator.
        return (bool) $user?->hasAnyRole(['superadmin', 'dpmd']);
    }

    use HasListTitle;

    public function mount(): void
    {
        parent::mount();

        // Kembali ke daftar Nagari → keluar dari SEMUA konteks menu (Warga/Wilayah/
        // UMKM), titik awal segar sebelum memilih "Kelola X" utk nagari lain.
        NagariContext::clearAll();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->color('primary'),
        ];
    }
}
