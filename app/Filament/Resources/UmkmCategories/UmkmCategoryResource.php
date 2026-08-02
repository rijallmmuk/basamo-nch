<?php

namespace App\Filament\Resources\UmkmCategories;

use App\Filament\Resources\UmkmCategories\Pages\ManageUmkmCategories;
use App\Models\UmkmCategory;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
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

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Taksonomi global lintas-nagari → hanya super admin (cegah satu nagari mengubah
    // kategori yang dipakai semua nagari).
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin()
            && parent::canAccess();
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
                        ->maxLength(100)
                        // Konsisten dgn Data Master: nama referensi global unik.
                        ->unique(ignoreRecord: true),

                    Textarea::make('panduan_produk')
                        ->label('Panduan deskripsi produk')
                        ->placeholder('Isi panduan deskripsi produk')
                        ->helperText('Tampil sebagai petunjuk saat pemilik menulis deskripsi produk kategori ini.')
                        ->rows(4)
                        ->columnSpanFull(),

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

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('warning'),
                    DeleteAction::make()
                        ->before(function (UmkmCategory $record, DeleteAction $action): void {
                            $count = $record->products()->count();

                            if ($count > 0) {
                                Notification::make()
                                    ->title('Kategori masih dipakai')
                                    ->body("Masih dipakai {$count} produk. Pindahkan kategorinya dulu sebelum menghapus.")
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
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
            'index' => ManageUmkmCategories::route('/'),
        ];
    }
}
