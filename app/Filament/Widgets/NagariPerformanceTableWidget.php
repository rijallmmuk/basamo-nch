<?php

namespace App\Filament\Widgets;

use App\Enums\StatusIdm;
use App\Models\Nagari;
use App\Services\PublicTerasAggregateService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NagariPerformanceTableWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rekap Performa Antar Nagari';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'dpmd'])
            && auth()->user()?->managedNagariId() === null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                app(PublicTerasAggregateService::class)->query()
            )
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nama Nagari')
                    ->description(fn (Nagari $record): ?string => $record->kabupaten)
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('total_penduduk')
                    ->label('Penduduk')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_warga')
                    ->label('Akun Warga Aktif')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_umkm')
                    ->label('Lapak Aktif')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_produk')
                    ->label('Produk')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('modul_selesai')
                    ->label('Modul Selesai')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('skor_sdgs')
                    ->label('Skor SDGs')
                    ->formatStateUsing(fn ($state): string => $state === null
                        ? '—'
                        : number_format((float) $state, 1, ',', '.').'%')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('sdg_terisi')
                    ->label('Data SDGs')
                    ->formatStateUsing(fn ($state): string => ((int) $state).' / 18')
                    ->alignCenter(),

                TextColumn::make('status_idm')
                    ->label('Status IDM')
                    ->formatStateUsing(fn ($state): string => StatusIdm::tryFrom((string) $state)?->label()
                        ?: ($state ?: 'Belum ada data'))
                    ->badge()
                    // Warna diambil dari enumnya sendiri supaya tabel ini, halaman
                    // Statistik IDM, dan grafik sebaran memakai kode warna yang sama.
                    ->color(fn ($state): string => StatusIdm::tryFrom((string) $state)?->color() ?? 'gray')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
