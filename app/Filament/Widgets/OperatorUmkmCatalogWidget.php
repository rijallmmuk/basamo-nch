<?php

namespace App\Filament\Widgets;

use App\Enums\ActiveStatus;
use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Models\UmkmProduct;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OperatorUmkmCatalogWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Produk UMKM Nagari';

    public function table(Table $table): Table
    {
        $nagariId = $this->nagariId();

        return $table
            ->description('Lapak aktif di '.$this->namaNagari().'.')
            ->query(
                UmkmProduct::query()
                    ->with(['umkmProfile', 'category', 'media'])
                    ->whereHas('umkmProfile', fn ($q) => $q
                        ->where('nagari_id', $nagariId)
                        ->where('status', ActiveStatus::Active))
                    ->orderByDesc('jumlah_dilihat')
            )
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                ImageColumn::make('cover')
                    ->label('Foto')
                    ->getStateUsing(fn (UmkmProduct $record): string => $record->coverUrl())
                    ->square()
                    ->imageSize(40),

                TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('umkmProfile.nama_usaha')
                    ->label('Lapak Usaha')
                    ->badge()
                    ->color('info'),

                TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                TextColumn::make('jumlah_dilihat')
                    ->label('Tayangan Etalase')
                    ->suffix('× dilihat')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
