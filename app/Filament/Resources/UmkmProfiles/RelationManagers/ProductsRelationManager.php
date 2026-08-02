<?php

namespace App\Filament\Resources\UmkmProfiles\RelationManagers;

use App\Filament\Resources\UmkmProducts\Schemas\UmkmProductForm;
use App\Filament\Resources\UmkmProducts\Support\UmkmProductActions;
use App\Filament\Resources\UmkmProducts\Tables\UmkmProductsTable;
use App\Filament\Resources\UmkmProducts\UmkmProductResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Daftar produk milik satu UMKM, dari halaman "Ubah Daftar UMKM" admin. Tabel
 * standar (baris, sama seperti tabel admin lain) — kolom & aksi Tinjau (Tolak/
 * Setujui di dalam modal) dipakai bersama dgn "Daftar Produk UMKM" ({@see
 * UmkmProductsTable}) agar admin tak perlu belajar pola berbeda di tiap
 * halaman. Aksi dikumpulkan dalam satu menu ⋮ (konvensi sama dgn tabel Warga).
 * Tambah/Ubah produk memakai field yang identik dgn form pengajuan warga
 * sendiri ({@see UmkmProductForm}).
 */
class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Produk';

    public function isReadOnly(): bool
    {
        return ! (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return UmkmProductForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_produk')
            ->recordUrl(fn (UmkmProduct $record): string => UmkmProductResource::getUrl('view', ['record' => $record]))
            // Seluruh baris di sini milik satu lapak, jadi kolom Usaha dan Nagari
            // hanya mengulang nilai yang sama.
            ->columns(UmkmProductsTable::columns(scopedToNagari: true, selfService: true))
            ->filters([
                SelectFilter::make('umkm_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'nama'),
            ])
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading('Belum ada produk')
            ->emptyStateDescription('Produk yang ditambahkan langsung tampil di etalase selama lapak ini tayang.')
            ->headerActions([
                // Tambah produk atas nama UMKM ini (pemilik atau operator). Begitu
                // disimpan, produk langsung tampil di etalase selama lapaknya aktif.
                // Menuju halaman Tambah Produk dengan lapak ini sudah terpilih, sama
                // seperti dari menu Produk. Formulirnya terlalu sesak untuk modal.
                CreateAction::make()->color('primary')
                    ->label('Tambah Produk')
                    ->authorize(fn (): bool => (auth()->user()?->can('create', UmkmProduct::class) ?? false)
                        && (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false))
                    ->url(fn (): string => UmkmProductResource::getUrl('create', [
                        'lapak' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn (UmkmProduct $record): string => UmkmProductResource::getUrl('view', ['record' => $record])),
                    // Ubah produk. Pemilik lewat UmkmService (foto dioptimasi);
                    // admin (moderasi) pakai simpan biasa.
                    EditAction::make()
                        ->color('warning')
                        ->modalHeading('Ubah Produk')
                        ->modalSubmitActionLabel('Simpan Perubahan')
                        ->using(function (UmkmProduct $record, array $data): UmkmProduct {
                            if (UmkmProfileResource::isSelfService()) {
                                return app(UmkmService::class)->updateProduct($record, $data);
                            }

                            $record->update($data);

                            return $record;
                        }),

                    UmkmProductActions::delete(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
