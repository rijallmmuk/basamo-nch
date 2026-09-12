<?php

namespace App\Filament\Resources\Beritas\Tables;

use App\Enums\KategoriBerita;
use App\Enums\StatusBerita;
use App\Filament\Resources\Beritas\BeritaResource;
use App\Models\Berita;
use App\Models\Nagari;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BeritasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Berita $record): string => BeritaResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->color('gray')
                    ->alignCenter(),

                ImageColumn::make('sampul')
                    ->label('Sampul')
                    ->getStateUsing(fn (Berita $record): ?string => $record->sampulUrl('card'))
                    ->square()
                    ->imageSize(44)
                    ->alignCenter(),

                TextColumn::make('judul')
                    ->label('Judul Berita / Pengumuman')
                    ->weight(FontWeight::Bold)
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->icon(fn (Berita $record): ?string => $record->is_pinned ? 'heroicon-m-bookmark' : null)
                    ->iconColor('warning')
                    ->iconPosition(IconPosition::After)
                    ->tooltip(fn (Berita $record): ?string => $record->is_pinned ? 'Disematkan sebagai Sorotan Utama' : null),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('cakupan')
                    ->label('Sasaran')
                    ->state(fn (Berita $record): string => $record->targetAudienceLabel())
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('published_at')
                    ->label('Waktu Terbit')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('views_count')
                    ->label('Dilihat')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(KategoriBerita::class),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusBerita::class),

                TernaryFilter::make('is_pinned')
                    ->label('Disematkan'),

                SelectFilter::make('nagari_id')
                    ->label('Nagari')
                    ->options(fn (): array => Nagari::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                TrashedFilter::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn (Berita $record): bool => ! $record->trashed() && (auth()->user()?->can('update', $record) ?? false)),
                    DeleteAction::make()
                        ->visible(fn (Berita $record): bool => (auth()->user()?->can('delete', $record) ?? false)),
                    RestoreAction::make()
                        ->visible(fn (Berita $record): bool => (auth()->user()?->can('restore', $record) ?? false)),
                    ForceDeleteAction::make()
                        ->visible(fn (Berita $record): bool => (auth()->user()?->can('forceDelete', $record) ?? false)),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
