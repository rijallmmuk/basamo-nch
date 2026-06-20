<?php

namespace App\Filament\Resources\UmkmProfiles\RelationManagers;

use App\Models\UmkmProduct;
use App\Notifications\UmkmProductVerified;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Produk';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_produk')
            ->columns([
                TextColumn::make('nama_produk')
                    ->label('Nama produk')
                    ->searchable(),

                TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('view_count')
                    ->label('Dilihat')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UmkmProduct $record): bool => $record->status !== 'approved')
                    ->requiresConfirmation()
                    ->action(fn (UmkmProduct $record) => $this->setStatus($record, 'approved')),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UmkmProduct $record): bool => $record->status !== 'rejected')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Alasan penolakan')
                            ->required()
                            ->helperText('Disampaikan ke pemilik agar bisa memperbaiki.'),
                    ])
                    ->action(fn (UmkmProduct $record, array $data) => $this->setStatus($record, 'rejected', $data['rejection_reason'])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** Form tak dipakai (produk dibuat pemilik di portal); verifikasi via aksi. */
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function setStatus(UmkmProduct $product, string $status, ?string $reason = null): void
    {
        $product->update([
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? $reason : null,
            'approved_by' => $status === 'approved' ? Auth::id() : null,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        // Beri tahu pemilik hasil verifikasi (notifikasi in-app portal).
        $product->umkmProfile->owner?->notify(new UmkmProductVerified($product));
    }
}
