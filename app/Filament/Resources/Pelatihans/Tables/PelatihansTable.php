<?php

namespace App\Filament\Resources\Pelatihans\Tables;

use App\Enums\StatusPelatihan;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use App\Models\Module;
use App\Models\Pelatihan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PelatihansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Pelatihan $record): string => PelatihanResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->color('gray')
                    ->alignCenter(),

                ViewColumn::make('cover')
                    ->label('Cover')
                    ->view('filament.tables.pelatihan-cover')
                    ->alignCenter(),

                TextColumn::make('tema.nama')
                    ->label('Tema Pelatihan')
                    ->weight(FontWeight::Bold)
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->icon(fn (Pelatihan $record): ?string => $record->trashed() ? 'heroicon-m-trash' : null)
                    ->iconColor('danger')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (Pelatihan $record): ?string => $record->trashed()
                        ? 'Dihapus '.$record->deleted_at?->translatedFormat('d M Y')
                        : null),

                TextColumn::make('cakupan')
                    ->label('Cakupan')
                    ->state(function (Pelatihan $record): string {
                        if ($record->semua_nagari) {
                            return 'Semua nagari';
                        }

                        $nagaris = $record->nagaris;

                        return match (true) {
                            $nagaris->isEmpty() => 'Belum ada',
                            $nagaris->count() === 1 => (string) $nagaris->first()->nama,
                            default => $nagaris->count().' nagari',
                        };
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('modules_count')
                    ->label('Modul')
                    ->counts('modules')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Akses Warga')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPelatihan::class),
                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn (Pelatihan $record): bool => ! $record->trashed()),
                    Action::make('tambahModul')
                        ->label('Tambah Modul')
                        ->icon(Heroicon::OutlinedPlus)
                        ->color('primary')
                        ->visible(fn (Pelatihan $record): bool => ! $record->trashed())
                        ->authorize(fn (Pelatihan $record): bool => (auth()->user()?->can('create', Module::class) ?? false)
                            && (auth()->user()?->can('kelolaKonten', $record) ?? false))
                        ->url(fn (Pelatihan $record): string => ModuleResource::getUrl('create', [
                            'pelatihan' => $record->getKey(),
                        ])),
                    ...self::statusActions(),
                    DeleteAction::make()
                        ->modalDescription('Menghapus pelatihan ini SEKALIGUS menyampah seluruh isinya: semua modul, materi, evaluasi, dan diskusi di dalamnya. Semua bisa dipulihkan bersama lewat Pulihkan.'),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->modalDescription('Menghapus PERMANEN pelatihan beserta seluruh modul, materi, evaluasi, dan diskusinya. Tindakan ini tidak dapat dibatalkan.'),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    /** @return list<Action> */
    private static function statusActions(): array
    {
        return [
            Action::make('buka')
                ->label('Buka untuk Warga')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->visible(fn (Pelatihan $record): bool => ! $record->trashed()
                    && $record->status !== StatusPelatihan::Terbuka)
                ->authorize(fn (Pelatihan $record): bool => auth()->user()?->can('kelolaStatus', $record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Warga di seluruh nagari sasaran dapat mengakses modulnya dan akan diberi notifikasi.')
                ->action(fn (Pelatihan $record) => self::ubahStatus($record, StatusPelatihan::Terbuka)),

            Action::make('kunci')
                ->label('Kunci')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->visible(fn (Pelatihan $record): bool => ! $record->trashed()
                    && $record->status !== StatusPelatihan::Terkunci)
                ->authorize(fn (Pelatihan $record): bool => auth()->user()?->can('kelolaStatus', $record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Warga tetap melihat pelatihan sebagai "Belum dibuka", tetapi tidak dapat mengakses isinya.')
                ->action(fn (Pelatihan $record) => self::ubahStatus($record, StatusPelatihan::Terkunci)),
        ];
    }

    private static function ubahStatus(Pelatihan $record, StatusPelatihan $status): void
    {
        try {
            PelatihanResource::setStatus($record, $status);
        } catch (\DomainException $e) {
            Notification::make()
                ->title('Status tidak dapat diubah')
                ->body($e->getMessage().' Tambahkan sasaran nagari dan minimal satu materi terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Status pelatihan diperbarui: '.$status->getLabel())
            ->success()
            ->send();
    }
}
