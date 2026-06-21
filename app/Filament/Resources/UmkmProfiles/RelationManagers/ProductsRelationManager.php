<?php

namespace App\Filament\Resources\UmkmProfiles\RelationManagers;

use App\Enums\UmkmProductStatus;
use App\Models\UmkmProduct;
use App\Services\UmkmService;
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
                    ->badge(),

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
                    ->options(UmkmProductStatus::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UmkmProduct $record): bool => $record->status !== UmkmProductStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (UmkmProduct $record) => $this->setStatus($record, UmkmProductStatus::Approved)),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UmkmProduct $record): bool => $record->status !== UmkmProductStatus::Rejected)
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Alasan penolakan')
                            ->required()
                            ->helperText('Disampaikan ke pemilik agar bisa memperbaiki.'),
                    ])
                    ->action(fn (UmkmProduct $record, array $data) => $this->setStatus($record, UmkmProductStatus::Rejected, $data['rejection_reason'])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** Form tak dipakai (produk dibuat pemilik di portal); verifikasi via aksi. */
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function setStatus(UmkmProduct $product, UmkmProductStatus $status, ?string $reason = null): void
    {
        app(UmkmService::class)->verifyProduct($product, $status, Auth::id(), $reason);
    }
}
