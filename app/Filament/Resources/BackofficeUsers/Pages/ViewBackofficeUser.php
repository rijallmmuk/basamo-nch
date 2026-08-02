<?php

namespace App\Filament\Resources\BackofficeUsers\Pages;

use App\Filament\Resources\BackofficeUsers\BackofficeUserResource;
use App\Filament\Resources\BackofficeUsers\Support\BackofficeUserActions;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackofficeUser extends ViewRecord
{
    protected static string $resource = BackofficeUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->color('warning'),
            ActionGroup::make([
                BackofficeUserActions::resetInitialPassword(),
                DeleteAction::make()
                    ->authorize(fn (User $record): bool => ! $record->hasRole('operator')
                        && $record->penduduk_id === null
                        && $record->getKey() !== auth()->id()),
                RestoreAction::make()
                    ->authorize(fn (User $record): bool => ! $record->hasRole('operator')
                        && $record->penduduk_id === null),
                ForceDeleteAction::make()
                    ->authorize(fn (User $record): bool => ! $record->hasRole('operator')
                        && $record->penduduk_id === null
                        && $record->getKey() !== auth()->id()),
            ])
                ->label('Aksi Akun')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }
}
