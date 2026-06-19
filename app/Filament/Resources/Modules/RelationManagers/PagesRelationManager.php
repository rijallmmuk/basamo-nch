<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'Halaman Materi';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Halaman')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('type')
                    ->label('Tipe Konten')
                    ->options([
                        'text' => '📝 Teks',
                        'video' => '🎬 Video',
                        'pdf' => '📄 PDF',
                    ])
                    ->required()
                    ->default('text')
                    ->live()
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Konten')
                    ->visible(fn ($get) => $get('type') === 'text')
                    ->columnSpanFull(),

                TextInput::make('video_url')
                    ->label('URL Video')
                    ->url()
                    ->placeholder('https://www.youtube.com/embed/...')
                    ->helperText('Paste link embed YouTube atau Google Drive')
                    ->visible(fn ($get) => $get('type') === 'video')
                    ->maxLength(500)
                    ->columnSpanFull(),

                FileUpload::make('file_path')
                    ->label('File PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->disk('public')
                    ->directory('modules/pages/pdf')
                    ->visibility('public')
                    ->visible(fn ($get) => $get('type') === 'pdf')
                    ->maxSize(10240) // 10 MB
                    ->helperText('Maksimal 10 MB, format PDF.')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->width('50px'),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => '📝 Teks',
                        'video' => '🎬 Video',
                        'pdf' => '📄 PDF',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'info',
                        'video' => 'success',
                        'pdf' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Halaman'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
