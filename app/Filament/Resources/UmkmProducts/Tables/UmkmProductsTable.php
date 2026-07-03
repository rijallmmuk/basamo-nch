<?php

namespace App\Filament\Resources\UmkmProducts\Tables;

use App\Enums\UmkmProductStatus;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UmkmProductsTable
{
    public static function configure(Table $table): Table
    {
        // Selalu ter-scope ke satu desa (desa_admin, atau super admin via
        // "Kelola › UMKM" — tanpa konteks super admin tak bisa akses),
        // jadi kolom Desa tidak diperlukan.
        return $table
            ->columns([
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
                    ->searchable(),

                TextColumn::make('umkmProfile.nama_usaha')
                    ->label('Usaha')
                    ->searchable(),

                TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(UmkmProductStatus::class)
                    ->default('pending'),
            ])
            ->recordActions([
                // SATU aksi "Tinjau" (keputusan user): modal detail (foto + deskripsi
                // + usaha) dulu, keputusan Setujui/Tolak dipilih DI DALAM modal —
                // pola sama dengan antrean Pengajuan UMKM.
                Action::make('tinjau')
                    ->label('Tinjau')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (UmkmProduct $record): string => 'Produk: '.$record->nama_produk)
                    ->modalContent(fn (UmkmProduct $record) => view('filament.umkm-product-detail', ['product' => $record]))
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Setujui Produk')
                    ->modalSubmitAction(fn ($action, UmkmProduct $record) => $action->hidden($record->status === UmkmProductStatus::Approved))
                    ->action(function (UmkmProduct $record): void {
                        app(UmkmService::class)->verifyProduct($record, UmkmProductStatus::Approved, Auth::id());

                        Notification::make()
                            ->title('Produk disetujui')
                            ->body("\"{$record->nama_produk}\" tayang di katalog publik. Pemilik diberi tahu.")
                            ->success()
                            ->send();
                    })
                    ->extraModalFooterActions([
                        Action::make('tolak')
                            ->label('Tolak…')
                            ->color('danger')
                            ->visible(fn (UmkmProduct $record): bool => $record->status !== UmkmProductStatus::Rejected)
                            ->modalHeading('Tolak produk')
                            ->modalDescription('Alasan ditampilkan ke pemilik — ia dapat memperbaiki lalu mengajukan ulang.')
                            ->modalSubmitActionLabel('Tolak')
                            ->schema([
                                Textarea::make('alasan_penolakan')
                                    ->label('Alasan penolakan')
                                    ->required()
                                    ->minLength(5)
                                    ->maxLength(1000)
                                    ->rows(3),
                            ])
                            ->action(function (UmkmProduct $record, array $data): void {
                                app(UmkmService::class)->verifyProduct($record, UmkmProductStatus::Rejected, Auth::id(), $data['alasan_penolakan']);

                                Notification::make()
                                    ->title('Produk ditolak')
                                    ->body('Pemilik diberi tahu beserta alasannya.')
                                    ->success()
                                    ->send();
                            })
                            ->cancelParentActions(),
                    ]),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
