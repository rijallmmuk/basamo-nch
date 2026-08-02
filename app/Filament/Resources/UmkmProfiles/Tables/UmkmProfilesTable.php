<?php

namespace App\Filament\Resources\UmkmProfiles\Tables;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\Support\UmkmProfileActions;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use App\Support\NagariContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UmkmProfilesTable
{
    public static function configure(Table $table): Table
    {
        $scopedToNagari = auth()->user()?->managedNagariId(NagariContext::UMKM_PROFIL) !== null;
        $selfService = UmkmProfileResource::isSelfService();

        $filters = $selfService ? [] : [
            SelectFilter::make('status')
                ->options(ActiveStatus::class),
            TrashedFilter::make(),
        ];

        if (! $scopedToNagari && ! $selfService) {
            array_unshift(
                $filters,
                SelectFilter::make('nagari')
                    ->label('Nagari')
                    ->relationship('nagari', 'nama')
                    ->searchable()
                    ->preload(),
            );
        }

        return $table
            ->recordUrl(fn (UmkmProfile $record): ?string => auth()->user()?->can('view', $record)
                ? UmkmProfileResource::getUrl('view', ['record' => $record])
                : null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama_usaha')
                    ->label('Nama usaha')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable()
                    ->hidden($selfService),

                TextColumn::make('nagari.nama')
                    ->label('Nagari')
                    ->sortable()
                    ->visible(! $scopedToNagari && ! $selfService),

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters($filters)
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->color('warning'),
                    UmkmProfileActions::toggleStatus(),
                    UmkmProfileActions::archive(),
                    UmkmProfileActions::restore(),
                    UmkmProfileActions::forceDelete(),
                ])
                    ->visible(! $selfService)
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
