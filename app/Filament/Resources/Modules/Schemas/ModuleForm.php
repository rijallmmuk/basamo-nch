<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Models\Module;
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
                    // Hanya super_admin yang menentukan nagari/global.
                    // nagari_admin: nagari_id diisi otomatis (lihat CreateModule).
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->default('draft')
                    ->required()
                    ->columnSpan(1),

                TextInput::make('estimated_minutes')
                    ->label('Estimasi Durasi')
                    ->helperText('Perkiraan lama belajar modul ini. Opsional.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->suffix('menit')
                    ->nullable()
                    ->columnSpan(1),

                Select::make('prerequisite_module_id')
                    ->label('Prasyarat Modul')
                    ->relationship(
                        name: 'prerequisite',
                        titleAttribute: 'title',
                        modifyQueryUsing: function ($query, ?Module $record) {
                            // Tidak boleh menjadikan modul sebagai prasyarat dirinya sendiri.
                            if ($record) {
                                $query->whereKeyNot($record->getKey());
                            }
                            // nagari_admin hanya boleh memilih prasyarat dari nagarinya sendiri.
                            if (auth()->user()?->isNagariAdmin()) {
                                $query->where('nagari_id', auth()->user()->nagari_id);
                            }
                        },
                    )
                    ->placeholder('— Tidak ada prasyarat —')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),

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
