<?php

namespace App\Filament\Resources\Discussions\Tables;

use App\Models\Discussion;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Notifications\DiscussionReplied;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscussionsTable
{
    public static function configure(Table $table): Table
    {
        $filters = [
            SelectFilter::make('read_status')
                ->label('Status Keterbacaan')
                ->options([
                    'unread' => 'Belum Dibaca',
                    'read' => 'Sudah Dibaca',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $user = auth()->user();
                    if (! $user || empty($data['value'])) {
                        return $query;
                    }

                    return match ($data['value']) {
                        'unread' => $query->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id)),
                        'read' => $query->whereHas('reads', fn ($q) => $q->where('user_id', $user->id)),
                        default => $query,
                    };
                }),

            SelectFilter::make('reply_status')
                ->label('Status Balasan')
                ->options([
                    'unanswered' => 'Belum Dibalas',
                    'answered' => 'Sudah Dibalas',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['value'])) {
                        return $query;
                    }

                    return match ($data['value']) {
                        'unanswered' => $query->doesntHave('replies'),
                        'answered' => $query->has('replies'),
                        default => $query,
                    };
                }),

            SelectFilter::make('nagari')
                ->label('Nagari Asal')
                ->options(fn () => Nagari::query()
                    ->when(auth()->user()?->isOperator(), fn ($nagaris) => $nagaris
                        ->whereKey(auth()->user()->nagari_id))
                    ->orderBy('nama')
                    ->pluck('nama', 'id'))
                ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                    ? $query->whereHas('user', fn ($q) => $q->where('nagari_id', $data['value']))
                    : $query),

            SelectFilter::make('pelatihan')
                ->label('Pelatihan')
                ->options(fn (): array => Pelatihan::query()
                    ->with(['tema', 'nagaris'])
                    ->when(auth()->user()?->isOperator(), fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->when(auth()->user()?->isPengajar(), fn (Builder $query) => $query->manageableBy(auth()->user()))
                    ->get()
                    ->sortBy(fn (Pelatihan $pelatihan): string => $pelatihan->namaTampil())
                    ->mapWithKeys(fn (Pelatihan $pelatihan): array => [
                        $pelatihan->getKey() => $pelatihan->namaTampil(),
                    ])
                    ->all())
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->whereHas('module', fn ($q) => $q->where('pelatihan_id', $data['value']))
                    : $query)
                ->searchable()
                ->preload(),

            SelectFilter::make('module')
                ->label('Modul')
                ->options(fn () => Module::query()
                    ->when(auth()->user()?->isOperator(), fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->when(auth()->user()?->isPengajar(), fn (Builder $query) => $query->manageableBy(auth()->user()))
                    ->orderBy('judul')
                    ->pluck('judul', 'id'))
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->where('module_id', $data['value'])
                    : $query)
                ->searchable()
                ->preload(),

            TernaryFilter::make('is_pinned')
                ->label('Disematkan'),

            TrashedFilter::make(),
        ];

        return $table
            ->recordUrl(null)
            ->columns([
                View::make('filament.tables.discussion-row')
                    ->extraAttributes([
                        'x-on:click' => 'isCollapsed = ! isCollapsed',
                        'class' => 'cursor-pointer',
                    ]),
                View::make('filament.tables.discussion-replies')
                    ->collapsible(),
            ])
            ->filters($filters)
            ->deferFilters(false)
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordClasses(fn (Discussion $record): ?string => (
                ! $record->trashed() && ! $record->isReadBy(auth()->user())
            ) ? 'nch-row-new' : null)
            ->recordActions([
                ActionGroup::make([
                    Action::make('markAsRead')
                        ->label('Tandai Dibaca')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Discussion $record): bool => ! $record->isReadBy(auth()->user())
                            && (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']))
                        ->action(function (Discussion $record, $livewire): void {
                            $record->markAsReadBy(auth()->user());

                            Notification::make()
                                ->title('Diskusi ditandai telah dibaca')
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-sidebar');
                        }),

                    Action::make('markAsUnread')
                        ->label('Tandai Belum Dibaca')
                        ->icon('heroicon-o-envelope-open')
                        ->color('gray')
                        ->visible(fn (Discussion $record): bool => $record->isReadBy(auth()->user())
                            && (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']))
                        ->action(function (Discussion $record, $livewire): void {
                            $record->markAsUnreadBy(auth()->user());

                            Notification::make()
                                ->title('Diskusi ditandai belum dibaca')
                                ->info()
                                ->send();

                            $livewire->dispatch('refresh-sidebar');
                        }),

                    Action::make('balas')
                        ->label('Balas')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->authorize('reply')
                        ->visible(fn (Discussion $record): bool => $record->parent_id === null
                            && ! $record->trashed()
                            && auth()->user()->can('reply', $record))
                        ->modalHeading('Balas pertanyaan')
                        ->modalSubmitActionLabel('Kirim balasan')
                        ->schema([
                            Textarea::make('isi')
                                ->label('Balasan')
                                ->placeholder('Tulis jawaban untuk warga')
                                ->required()
                                ->minLength(2)
                                ->maxLength(2000)
                                ->rows(4),
                        ])
                        ->action(function (Discussion $record, array $data, $livewire): void {
                            $record->replies()->create([
                                'module_id' => $record->module_id,
                                'user_id' => auth()->id(),
                                'isi' => $data['isi'],
                            ]);

                            $record->markAsReadBy(auth()->user());

                            if ($record->user_id !== auth()->id() && $record->user) {
                                $record->user->notify(new DiscussionReplied($record, auth()->user()->name));
                            }

                            Notification::make()
                                ->title('Balasan terkirim')
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-sidebar');
                        }),

                    Action::make('togglePin')
                        ->label(fn (Discussion $record): string => $record->is_pinned ? 'Lepas sematan' : 'Sematkan')
                        ->icon(fn (Discussion $record): string => $record->is_pinned ? 'heroicon-o-bookmark-slash' : 'heroicon-o-bookmark')
                        ->color('warning')
                        ->authorize('update')
                        ->visible(fn (Discussion $record): bool => $record->parent_id === null
                            && ! $record->trashed()
                            && auth()->user()->can('update', $record))
                        ->action(function (Discussion $record): void {
                            $record->update(['is_pinned' => ! $record->is_pinned]);

                            Notification::make()
                                ->title($record->is_pinned ? 'Diskusi disematkan' : 'Sematan dilepas')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()->after(fn ($livewire) => $livewire->dispatch('refresh-sidebar')),
                    RestoreAction::make()->after(fn ($livewire) => $livewire->dispatch('refresh-sidebar')),
                    ForceDeleteAction::make()->after(fn ($livewire) => $livewire->dispatch('refresh-sidebar')),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ]);
    }
}
