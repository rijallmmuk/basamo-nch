<?php

namespace App\Filament\Resources\DataMaster;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Basis CRUD data master (tabel referensi global): bentuknya sama — `nama` unik,
 * `urutan`, `aktif`. Hanya super admin (referensi dipakai lintas-desa).
 *
 * Menghapus baris yang masih dipakai DIBLOKIR (FK-nya SET NULL/RESTRICT — hapus
 * paksa = data penduduk/desa hilang diam-diam atau error). Jalur yang benar
 * untuk "memensiunkan" nilai: toggle Nonaktif — hilang dari pilihan form,
 * data lama tetap utuh.
 */
abstract class LookupResource extends Resource
{
    /** Nama relasi pemakai (untuk kolom "Dipakai" & guard hapus). */
    protected static string $usageRelation;

    /** Sebutan pemakai pada pesan guard, mis. "warga" / "desa". */
    protected static string $usageLabel;

    /** Panjang maksimal kolom nama (pekerjaan 100, lainnya 50). */
    protected static int $namaMaxLength = 50;

    public static function getNavigationGroup(): ?string
    {
        return 'Data Master';
    }

    // Referensi global lintas-desa → hanya super admin.
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data')
                ->icon(Heroicon::OutlinedListBullet)
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    ...static::extraFormFields(),

                    TextInput::make('nama')
                        ->label('Nama')
                        ->required()
                        ->maxLength(static::$namaMaxLength)
                        ->unique(ignoreRecord: true),

                    TextInput::make('urutan')
                        ->label('Urutan')
                        ->helperText('Menentukan urutan tampil di pilihan form. Bisa juga diatur dengan menyeret baris di tabel.')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),

                    Toggle::make('aktif')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Nonaktifkan agar tidak muncul lagi di pilihan form — data lama yang sudah memakainya tetap utuh.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                ...static::extraColumns(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make(static::$usageRelation.'_count')
                    ->label('Dipakai')
                    ->counts(static::$usageRelation)
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Model $record, DeleteAction $action): void {
                        $count = $record->{static::$usageRelation}()->count();

                        if ($count > 0) {
                            Notification::make()
                                ->title('Tidak bisa dihapus — masih dipakai')
                                ->body("Masih dipakai {$count} ".static::$usageLabel.'. Nonaktifkan saja agar tidak muncul lagi di pilihan form.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }

    /**
     * Field tambahan sebelum `nama` (mis. `kode` pada Pekerjaan).
     *
     * @return array<int, Field>
     */
    protected static function extraFormFields(): array
    {
        return [];
    }

    /**
     * Kolom tabel tambahan sebelum `nama`.
     *
     * @return array<int, Column>
     */
    protected static function extraColumns(): array
    {
        return [];
    }
}
