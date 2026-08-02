<?php

namespace App\Filament\Resources\KontakMasuks;

use App\Enums\KategoriKontak;
use App\Filament\Resources\KontakMasuks\Pages\ListKontakMasuks;
use App\Models\KontakMasuk;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pesan masuk dari section "Hubungi Kami" beranda publik (base URL) — Jadi
 * Mitra / Keluhan / Saran. Hanya superadmin (konten platform, bukan per-nagari).
 * Read-only (tanpa create/edit) — dibuat publik lewat KontakController.
 */
class KontakMasukResource extends Resource
{
    protected static ?string $model = KontakMasuk::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Situs Publik';
    }

    public static function canAccess(): bool
    {
        return (auth()->user()?->isSuperAdmin() ?? false) && parent::canAccess();
    }

    public static function getModelLabel(): string
    {
        return 'Kontak Masuk';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kontak Masuk';
    }

    /** Badge = laporan yang belum ditandai dibaca oleh Superadmin ini. */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('no_hp')
                    ->label('No. HP'),

                TextColumn::make('nama_nagari')
                    ->label('Nagari')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (KontakMasuk $record): string => $record->balasan ? 'Dibalas' : 'Menunggu')
                    ->color(fn (string $state): string => match ($state) {
                        'Dibalas' => 'success',
                        'Menunggu' => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (KontakMasuk $record): ?string => (
                ! $record->trashed() && ! $record->isReadBy(auth()->user())
            ) ? 'nch-row-new' : null)
            ->filters([
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
                            'unread' => $query->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user->id)),
                            'read' => $query->whereHas('reads', fn ($reads) => $reads->where('user_id', $user->id)),
                            default => $query,
                        };
                    }),
                SelectFilter::make('kategori')
                    ->options(KategoriKontak::class),
                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    Action::make('markAsRead')
                        ->label('Tandai Dibaca')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (KontakMasuk $record): bool => ! $record->isReadBy(auth()->user()))
                        ->action(function (KontakMasuk $record, $livewire): void {
                            $record->markAsReadBy(auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->title('Laporan ditandai telah dibaca')
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-sidebar');
                        }),
                    Action::make('markAsUnread')
                        ->label('Tandai Belum Dibaca')
                        ->icon('heroicon-o-envelope-open')
                        ->color('gray')
                        ->visible(fn (KontakMasuk $record): bool => $record->isReadBy(auth()->user()))
                        ->action(function (KontakMasuk $record, $livewire): void {
                            $record->markAsUnreadBy(auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->title('Laporan ditandai belum dibaca')
                                ->info()
                                ->send();

                            $livewire->dispatch('refresh-sidebar');
                        }),
                    Action::make('lihat')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->authorize('view')
                        ->modalHeading(fn (KontakMasuk $record): string => $record->kategori->getLabel().' — '.$record->nama)
                        ->modalContent(fn (KontakMasuk $record) => view('filament.kontak-masuk-detail', ['kontak' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                    Action::make('balas')
                        ->label('Tindak Lanjut')
                        ->icon('heroicon-o-chat-bubble-bottom-center-text')
                        ->color('success')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('balasan')
                                ->label('Pesan Balasan')
                                ->required()
                                ->rows(4),
                        ])
                        ->action(function (KontakMasuk $record, array $data, $livewire): void {
                            $record->update([
                                'balasan' => $data['balasan'],
                                'balasan_dibaca_at' => null,
                            ]);
                            $record->markAsReadBy(auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil dibalas')
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-sidebar');
                        }),
                    DeleteAction::make()->after(fn ($livewire) => $livewire->dispatch('refresh-sidebar')),
                    RestoreAction::make()->after(fn ($livewire) => $livewire->dispatch('refresh-sidebar')),
                    ForceDeleteAction::make()->after(fn ($livewire) => $livewire->dispatch('refresh-sidebar')),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKontakMasuks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['reads' => fn ($query) => $user ? $query->where('user_id', $user->id) : $query]);
    }
}
