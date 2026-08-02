<?php

namespace App\Filament\Resources\BackofficeUsers\Tables;

use App\Filament\Resources\BackofficeUsers\BackofficeUserResource;
use App\Filament\Resources\BackofficeUsers\Support\BackofficeUserActions;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BackofficeUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (User $record): string => BackofficeUserResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('bold'),
                TextColumn::make('username')->label('Username')->searchable()->copyable(),
                TextColumn::make('nik')->label('NIK')->searchable()->copyable()->sortable(),
                TextColumn::make('roles.name')->label('Peran')->badge(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->relationship('roles', 'name'),
                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->color('warning'),
                    BackofficeUserActions::resetInitialPassword(),
                    DeleteAction::make()
                        ->authorize(fn (User $record): bool => ! $record->hasRole('operator')
                            && $record->penduduk_id === null
                            && $record->getKey() !== auth()->id())
                        ->modalDescription(fn (User $record): ?string => BackofficeUserResource::turnoverWarning($record)),
                    RestoreAction::make()
                        ->authorize(fn (User $record): bool => ! $record->hasRole('operator')
                            && $record->penduduk_id === null),
                    ForceDeleteAction::make()
                        ->authorize(fn (User $record): bool => ! $record->hasRole('operator')
                            && $record->penduduk_id === null
                            && $record->getKey() !== auth()->id())
                        ->modalDescription(fn (User $record): ?string => BackofficeUserResource::turnoverWarning($record)),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ]);
    }
}
