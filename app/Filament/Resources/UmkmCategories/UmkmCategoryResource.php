<?php

namespace App\Filament\Resources\UmkmCategories;

use App\Filament\Resources\UmkmCategories\Pages\ManageUmkmCategories;
use App\Models\UmkmCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UmkmCategoryResource extends Resource
{
    protected static ?string $model = UmkmCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Taksonomi global lintas-desa → hanya super admin (cegah satu desa mengubah
    // kategori yang dipakai semua desa).
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getModelLabel(): string
    {
        return 'Kategori UMKM';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori UMKM';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Kategori')
                ->icon(Heroicon::OutlinedTag)
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama kategori')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('icon')
                        ->label('Ikon (Heroicon)')
                        ->placeholder('heroicon-o-cake')
                        ->helperText('Nama ikon Heroicon, mis. heroicon-o-cake. Opsional.')
                        ->maxLength(60),

                    // Urutan TIDAK diisi lewat form: kategori baru otomatis di urutan
                    // terakhir; mengubah urutan = seret baris di tabel.
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

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('icon')
                    ->label('Ikon')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('profiles_count')
                    ->label('UMKM')
                    ->counts('profiles')
                    ->badge()
                    ->color('gray')
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
                // Kuning = konvensi aksi Ubah yang tampil langsung (sama dgn Wilayah & Data Master).
                EditAction::make()
                    ->color('warning'),
                // Kategori terpakai tidak boleh dihapus diam-diam (FK SET NULL akan
                // melepas kategori dari UMKM tanpa jejak) — konsisten dgn Data Master.
                DeleteAction::make()
                    ->before(function (UmkmCategory $record, DeleteAction $action): void {
                        $count = $record->profiles()->count();

                        if ($count > 0) {
                            Notification::make()
                                ->title('Tidak bisa dihapus — masih dipakai')
                                ->body("Masih dipakai {$count} UMKM. Pindahkan kategorinya dulu sebelum menghapus.")
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUmkmCategories::route('/'),
        ];
    }
}
