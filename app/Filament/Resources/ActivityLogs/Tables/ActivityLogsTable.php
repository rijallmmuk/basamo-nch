<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    private const EVENT_LABELS = [
        'created' => 'Dibuat',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
        'restored' => 'Dipulihkan',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->default('Sistem')
                    ->description(fn ($record) => $record->causer?->role),

                TextColumn::make('log_name')
                    ->label('Objek')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::EVENT_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('subject_id')
                    ->label('ID Objek')
                    ->formatStateUsing(fn ($state): string => $state ? '#'.$state : '—'),

                TextColumn::make('properties')
                    ->label('Perubahan')
                    ->formatStateUsing(fn ($record): string => self::formatChanges($record))
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Objek')
                    ->options([
                        'modul' => 'Modul',
                        'kuis' => 'Kuis',
                        'materi' => 'Materi',
                        'desa' => 'Desa',
                        'pengguna' => 'Pengguna',
                    ]),

                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options(self::EVENT_LABELS),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Ringkas properti aktivitas menjadi "field: lama → baru".
     */
    private static function formatChanges($record): string
    {
        $attributes = (array) ($record->properties['attributes'] ?? []);
        $old = (array) ($record->properties['old'] ?? []);

        if ($attributes === []) {
            return '—';
        }

        $lines = [];

        foreach ($attributes as $key => $new) {
            $newValue = self::stringify($new);

            if (array_key_exists($key, $old)) {
                $lines[] = "{$key}: ".self::stringify($old[$key]).' → '.$newValue;
            } else {
                $lines[] = "{$key}: {$newValue}";
            }
        }

        return implode('; ', $lines);
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            is_null($value) => '∅',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_UNICODE) ?: '—',
        };
    }
}
