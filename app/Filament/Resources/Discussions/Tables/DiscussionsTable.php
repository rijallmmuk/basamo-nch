<?php

namespace App\Filament\Resources\Discussions\Tables;

use App\Models\Desa;
use App\Models\Discussion;
use App\Notifications\DiscussionReplied;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
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

        // Tabel hanya berisi PERTANYAAN (balasan disaring di resource) → tanpa filter Tipe.
        $filters = [
            SelectFilter::make('module')
                ->label('Modul')
                ->relationship('module', 'judul')
                ->searchable()
                ->preload(),

            TernaryFilter::make('is_pinned')
                ->label('Disematkan'),

            TrashedFilter::make(),
        ];

        // Filter desa hanya untuk super_admin (desa_admin sudah ter-scope).
        if ($isSuperAdmin) {
            array_splice($filters, 1, 0, [
                SelectFilter::make('desa')
                    ->label('Desa')
                    ->options(fn () => Desa::orderBy('nama')->pluck('nama', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('user', fn ($q) => $q->where('desa_id', $data['value']))
                        : $query),
            ]);
        }

        return $table
            // Baris tidak dapat diklik (moderasi read-only; aksi lewat tombol).
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('module.judul')
                    ->label('Modul')
                    ->limit(30)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Penulis')
                    ->searchable(),

                TextColumn::make('user.desa.nama')
                    ->label('Desa')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->visible($isSuperAdmin),

                TextColumn::make('isi')
                    ->label('Isi')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->searchable(),

                TextColumn::make('replies_count')
                    ->label('Balasan')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                IconColumn::make('is_pinned')
                    ->label('Disematkan')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable()
                    ->alignCenter(),
            ])
            ->filters($filters)
            ->recordActions([
                // Admin (super/desa, sesuai scope) bisa menjawab pertanyaan warga.
                // Balasan ditulis atas nama admin yang login; hanya pada pertanyaan
                // (top-level) yang belum dihapus.
                Action::make('balas')
                    ->label('Balas')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->visible(fn (Discussion $record): bool => $record->parent_id === null
                        && ! $record->trashed()
                        && auth()->user()->can('update', $record))
                    ->modalHeading('Balas pertanyaan')
                    ->modalSubmitActionLabel('Kirim balasan')
                    ->schema([
                        Textarea::make('isi')
                            ->label('Balasan')
                            ->required()
                            ->minLength(2)
                            ->maxLength(2000)
                            ->rows(4),
                    ])
                    ->action(function (Discussion $record, array $data): void {
                        $record->replies()->create([
                            'module_id' => $record->module_id,
                            'user_id' => auth()->id(),
                            'isi' => $data['isi'],
                        ]);

                        // Beri tahu warga penanya bahwa pertanyaannya sudah dijawab admin.
                        if ($record->user_id !== auth()->id() && $record->user) {
                            $record->user->notify(new DiscussionReplied($record, auth()->user()->name));
                        }

                        Notification::make()
                            ->title('Balasan terkirim')
                            ->success()
                            ->send();
                    }),

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
