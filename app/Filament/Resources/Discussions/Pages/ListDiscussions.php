<?php

namespace App\Filament\Resources\Discussions\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Discussions\DiscussionResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDiscussions extends ListRecords
{
    protected static string $resource = DiscussionResource::class;

    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllAsRead')
                ->label('Tandai Semua Telah Dibaca')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar']))
                ->action(function (): void {
                    $user = auth()->user();
                    if (! $user) {
                        return;
                    }

                    $discussions = DiscussionResource::getEloquentQuery()
                        ->whereNull('deleted_at')
                        ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
                        ->get();

                    foreach ($discussions as $discussion) {
                        $discussion->markAsReadBy($user);
                    }

                    Notification::make()
                        ->title('Seluruh diskusi ditandai telah dibaca')
                        ->success()
                        ->send();

                    $this->dispatch('refresh-sidebar');
                }),
        ];
    }
}
