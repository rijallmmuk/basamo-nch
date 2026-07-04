<?php

namespace App\Filament\Resources\Discussions\Tables;

use App\Models\Desa;
use App\Models\Discussion;
use App\Notifications\DiscussionReplied;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\View;
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
            // Tiap pertanyaan = satu "kartu"; balasannya dibuka via baris expand
            // (View collapsible di bawah). Menandai baris terhapus dgn ikon trash pada
            // teks pertanyaan (konvensi) — kolom "Dihapus" terpisah tak lagi perlu.
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('isi')
                            ->weight(FontWeight::Bold)
                            ->wrap()
                            ->searchable()
                            ->icon(fn (Discussion $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                            ->iconColor('danger')
                            ->tooltip(fn (Discussion $record): ?string => $record->trashed()
                                ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y H:i')
                                : null),

                        // Meta penulis + waktu (baris sekunder ringan).
                        TextColumn::make('user.name')
                            ->color('gray')
                            ->icon('heroicon-m-user-circle')
                            ->iconColor('gray')
                            ->searchable()
                            ->formatStateUsing(fn (?string $state, Discussion $record): string => ($state ?? '—')
                                .' · '.$record->created_at->translatedFormat('d M Y, H:i')),

                        // Modul asal pertanyaan sebagai badge (mudah dipindai).
                        TextColumn::make('module.judul')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-m-book-open')
                            ->searchable()
                            ->grow(false),
                    ])->space(2),

                    Stack::make([
                        TextColumn::make('user.desa.nama')
                            ->badge()
                            ->color('gray')
                            ->grow(false)
                            ->visible($isSuperAdmin),

                        // Badge menonjol bila ada balasan (primary), abu bila kosong.
                        TextColumn::make('replies_count')
                            ->badge()
                            ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->formatStateUsing(fn (int $state): string => $state.' balasan')
                            ->grow(false),

                        // Hanya muncul saat disematkan (ikon & label sama-sama kondisional
                        // agar tak ada badge/ikon nyasar pada baris yang tidak disematkan).
                        TextColumn::make('is_pinned')
                            ->badge()
                            ->color('warning')
                            ->icon(fn (bool $state): ?string => $state ? 'heroicon-s-bookmark' : null)
                            ->formatStateUsing(fn (bool $state): ?string => $state ? 'Disematkan' : null)
                            ->grow(false),
                    ])
                        ->alignment(Alignment::End)
                        ->grow(false)
                        ->visibleFrom('md'),
                ]),

                View::make('filament.tables.discussion-replies')
                    ->collapsible(),
            ])
            ->filters($filters)
            // Filter LANGSUNG kepakai (bukan deferred) — penting agar tautan dari aksi
            // "Kelola Diskusi" di daftar Modul (?tableFilters[module][value]=…) menyaring
            // saat halaman dibuka, bukan menunggu tombol "Terapkan".
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
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
                    ->icon('heroicon-m-squares-2x2')
                    ->tooltip('Aksi'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
