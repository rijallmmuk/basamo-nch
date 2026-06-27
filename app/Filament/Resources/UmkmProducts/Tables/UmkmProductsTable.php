<?php

namespace App\Filament\Resources\UmkmProducts\Tables;

use App\Enums\UmkmProductStatus;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UmkmProductsTable
{
    public static function configure(Table $table): Table
    {
        // Ter-scope ke satu desa (desa_admin, atau super admin via "Kelola › UMKM")
        // → kolom Desa tak relevan.
        $scopedToDesa = auth()->user()?->managedDesaId() !== null;

        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('umkmProfile.nama_usaha')
                    ->label('Usaha')
                    ->searchable(),

                TextColumn::make('umkmProfile.desa.nama')
                    ->label('Desa')
                    ->visible(! $scopedToDesa),

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
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UmkmProduct $record): bool => $record->status !== UmkmProductStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (UmkmProduct $record) => app(UmkmService::class)
                        ->verifyProduct($record, UmkmProductStatus::Approved, Auth::id())),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UmkmProduct $record): bool => $record->status !== UmkmProductStatus::Rejected)
                    ->schema([
                        Textarea::make('alasan_penolakan')
                            ->label('Alasan penolakan')
                            ->required()
                            ->helperText('Disampaikan ke pemilik agar bisa memperbaiki.'),
                    ])
                    ->action(fn (UmkmProduct $record, array $data) => app(UmkmService::class)
                        ->verifyProduct($record, UmkmProductStatus::Rejected, Auth::id(), $data['alasan_penolakan'])),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
