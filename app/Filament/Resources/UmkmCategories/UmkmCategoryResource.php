<?php

namespace App\Filament\Resources\UmkmCategories;

use App\Filament\Resources\UmkmCategories\Pages\ManageUmkmCategories;
use App\Models\UmkmCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UmkmCategoryResource extends Resource
{
    protected static ?string $model = UmkmCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
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
            TextInput::make('nama')
                ->label('Nama kategori')
                ->required()
                ->maxLength(100),

            TextInput::make('icon')
                ->label('Ikon (Heroicon)')
                ->placeholder('heroicon-o-cake')
                ->helperText('Nama ikon Heroicon, mis. heroicon-o-cake. Opsional.')
                ->maxLength(60),

            TextInput::make('urutan')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->color('gray'),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('urutan')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUmkmCategories::route('/'),
        ];
    }
}
