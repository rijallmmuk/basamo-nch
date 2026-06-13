<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Models\Module;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Modul')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpan(1),

                TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->maxLength(255)
                    ->unique(Module::class, 'slug', ignoreRecord: true)
                    ->readOnly()
                    ->dehydrated()
                    ->columnSpan(1),

                Select::make('nagari_id')
                    ->label('Nagari')
                    ->relationship('nagari', 'nama')
                    ->placeholder('— Global (semua nagari) —')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                    ])
                    ->default('draft')
                    ->required()
                    ->columnSpan(1),

                Select::make('prerequisite_module_id')
                    ->label('Prasyarat Modul')
                    ->relationship('prerequisite', 'title')
                    ->placeholder('— Tidak ada prasyarat —')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),

                TextInput::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->columnSpan(1),

                FileUpload::make('thumbnail')
                    ->label('Thumbnail')
                    ->image()
                    ->directory('modules/thumbnails')
                    ->nullable()
                    ->columnSpanFull(),

                RichEditor::make('description')
                    ->label('Deskripsi')
                    ->nullable()
                    ->columnSpanFull(),

                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ])
            ->columns(2);
    }
}
