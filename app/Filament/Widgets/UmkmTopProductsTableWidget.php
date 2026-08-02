<?php

namespace App\Filament\Widgets;

use App\Models\UmkmProduct;
use App\Support\Dashboard\UmkmKunjunganData;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UmkmTopProductsTableWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Produk Paling Banyak Dilihat';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasUmkmAccess() && $user->umkmProfile()->exists());
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $profileId = $user?->umkmProfile?->id;

        return $table
            ->description('Total dihitung sejak produk dibuat. Kolom 30 hari terisi sejak pencatatan harian berjalan.')
            ->query(
                UmkmProduct::query()
                    ->with(['category', 'media'])
                    ->where('umkm_profile_id', $profileId ?? 0)
                    ->withSum(
                        ['views as kunjungan_terkini' => fn ($views) => $views
                            ->where('tanggal', '>=', UmkmKunjunganData::sejak()->toDateString())],
                        'jumlah'
                    )
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

                TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('kunjungan_terkini')
                    ->label('30 Hari Terakhir')
                    ->numeric()
                    ->default(0)
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('jumlah_dilihat')
                    ->label('Total Kunjungan')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
