<?php

namespace App\Filament\Resources\Discussions\Tables;

use App\Models\Discussion;
use App\Models\Nagari;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DiscussionsTable
{
    public static function configure(Table $table): Table
    {
        $isSuperAdmin = (bool) auth()->user()?->isSuperAdmin();

        $filters = [
            SelectFilter::make('tipe')
                ->label('Tipe')
                ->options(['thread' => 'Pertanyaan', 'reply' => 'Balasan'])
                ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                    'thread' => $query->whereNull('parent_id'),
                    'reply' => $query->whereNotNull('parent_id'),
                    default => $query,
                }),

            SelectFilter::make('module')
                ->label('Modul')
                ->relationship('module', 'title')
                ->searchable()
                ->preload(),

            TernaryFilter::make('is_pinned')
                ->label('Disematkan'),

            TrashedFilter::make(),
        ];

        // Filter nagari hanya untuk super_admin (nagari_admin sudah ter-scope).
        if ($isSuperAdmin) {
            array_splice($filters, 2, 0, [
                SelectFilter::make('nagari')
                    ->label('Nagari')
                    ->options(fn () => Nagari::orderBy('nama')->pluck('nama', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('user', fn ($q) => $q->where('nagari_id', $data['value']))
                        : $query),
            ]);
        }

        return $table
            ->columns([
                TextColumn::make('parent_id')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Balasan' : 'Pertanyaan')
                    ->color(fn ($state): string => $state ? 'gray' : 'info'),

                TextColumn::make('module.title')
                    ->label('Modul')
                    ->limit(30)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Penulis')
                    ->searchable(),

                TextColumn::make('user.nagari.nama')
                    ->label('Nagari')
                    ->badge()
                    ->color('gray')
                    ->visible($isSuperAdmin),

                TextColumn::make('body')
                    ->label('Isi')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->searchable(),

                TextColumn::make('replies_count')
                    ->label('Balasan')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state, Discussion $record): string => $record->parent_id ? '—' : (string) $state),

                IconColumn::make('is_pinned')
                    ->label('Disematkan')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters($filters)
            ->recordActions([
                Action::make('togglePin')
                    ->label(fn (Discussion $record): string => $record->is_pinned ? 'Lepas sematan' : 'Sematkan')
                    ->icon(fn (Discussion $record): string => $record->is_pinned ? 'heroicon-o-bookmark-slash' : 'heroicon-o-bookmark')
                    ->color('warning')
                    // Pin hanya untuk pertanyaan (top-level) yang belum dihapus.
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

                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
