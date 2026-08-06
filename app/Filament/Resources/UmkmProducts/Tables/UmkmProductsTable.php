<?php

namespace App\Filament\Resources\UmkmProducts\Tables;

use App\Filament\Resources\UmkmProducts\Schemas\UmkmProductForm;
use App\Filament\Resources\UmkmProducts\Support\UmkmProductActions;
use App\Filament\Resources\UmkmProducts\UmkmProductResource;
use App\Models\UmkmProduct;
use App\Support\NagariContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UmkmProductsTable
{
    public static function configure(Table $table): Table
    {
        $scopedToNagari = auth()->user()?->managedNagariId(NagariContext::UMKM_PRODUK) !== null;
        $selfService = UmkmProductResource::isSelfService();

        $filters = $selfService ? [] : [
            SelectFilter::make('umkm_category_id')
                ->label('Kategori')
                ->relationship('category', 'nama'),
        ];

        if (! $scopedToNagari && ! $selfService) {
            array_unshift(
                $filters,
                SelectFilter::make('nagari')
                    ->label('Nagari')
                    ->relationship('umkmProfile.nagari', 'nama')
                    ->searchable()
                    ->preload(),
            );
        }

        return $table
            ->columns(static::columns($scopedToNagari, $selfService))
            ->recordUrl(fn (UmkmProduct $record): ?string => auth()->user()?->can('view', $record)
                ? UmkmProductResource::getUrl('view', ['record' => $record])
                : null)
            ->filters($filters)
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading($selfService ? 'Belum ada produk' : 'Belum ada produk UMKM')
            ->emptyStateDescription($selfService
                ? 'Tambahkan produk pertama Anda. Setiap produk yang disimpan langsung tampil di etalase selama usaha Anda tayang.'
                : 'Tambahkan produk atas nama lapak warga, atau tunggu pemilik usaha mengelolanya sendiri.')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning')
                        ->modalHeading('Ubah Produk')
                        ->modalSubmitActionLabel('Simpan Perubahan')
                        ->schema(UmkmProductForm::components()),
                    UmkmProductActions::delete(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function columns(bool $scopedToNagari = false, bool $selfService = false): array
    {
        return [
            TextColumn::make('no')
                ->label('No.')
                ->rowIndex()
                ->alignCenter(),

            ImageColumn::make('cover')
                ->label('Foto')
                ->getStateUsing(fn (UmkmProduct $record): string => $record->coverUrl())
                ->square()
                ->imageSize(44),

            TextColumn::make('nama_produk')
                ->label('Produk')
                ->searchable()
                ->sortable()
                ->wrap(),

            // Pemilik hanya melihat produknya sendiri, jadi kolom nama usaha
            // hanya akan mengulang satu nilai yang sama di setiap baris.
            TextColumn::make('umkmProfile.nama_usaha')
                ->label('Usaha')
                ->searchable()
                ->sortable()
                ->visible(! $selfService),

            TextColumn::make('umkmProfile.nagari.nama')
                ->label('Nagari')
                ->badge()
                ->color('info')
                ->sortable()
                ->visible(! $scopedToNagari && ! $selfService),

            TextColumn::make('category.nama')
                ->label('Kategori')
                ->badge()
                ->color('gray')
                ->placeholder('—')
                ->sortable()
                ->alignCenter(),

            TextColumn::make('harga')
                ->label('Harga')
                ->money('IDR')
                ->placeholder('—')
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d M Y')
                ->sortable()
                ->alignCenter(),
        ];
    }
}
