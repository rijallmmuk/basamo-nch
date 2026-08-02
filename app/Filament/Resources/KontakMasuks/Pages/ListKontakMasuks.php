<?php

namespace App\Filament\Resources\KontakMasuks\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\KontakMasuks\KontakMasukResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListKontakMasuks extends ListRecords
{
    protected static string $resource = KontakMasukResource::class;

    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllAsRead')
                ->label('Tandai Semua Telah Dibaca')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function (): void {
                    $user = auth()->user();

                    if (! $user) {
                        return;
                    }

                    $laporans = KontakMasukResource::getEloquentQuery()
                        ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
                        ->get();

                    foreach ($laporans as $laporan) {
                        $laporan->markAsReadBy($user);
                    }

                    Notification::make()
                        ->title('Seluruh laporan ditandai telah dibaca')
                        ->success()
                        ->send();

                    $this->dispatch('refresh-sidebar');
                }),
        ];
    }
}
