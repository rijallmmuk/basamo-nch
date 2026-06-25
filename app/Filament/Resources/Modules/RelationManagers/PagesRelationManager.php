<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\ModulePageType;
use App\Enums\ModuleStatus;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                TextInput::make('judul')
                    ->label('Judul Halaman')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('tipe')
                    ->label('Tipe Konten')
                    ->options(ModulePageType::class)
                    ->required()
                    ->default('text')
                    ->live()
                    ->columnSpanFull(),

                RichEditor::make('konten')
                    ->label('Konten')
                    ->visible(fn ($get) => $get('tipe') === 'text')
                    ->required(fn ($get) => $get('tipe') === 'text')
                    ->columnSpanFull(),

                TextInput::make('url_video')
                    ->label('URL Video')
                    ->url()
                    // Hanya skema http/https (cegah javascript:/data: yang dirender di tautan).
                    ->rule(static function (): Closure {
                        return function (string $attribute, $value, Closure $fail): void {
                            if (filled($value) && ! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                                $fail('URL video harus diawali http:// atau https://.');
                            }
                        };
                    })
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->helperText('Tempel link YouTube atau Google Drive biasa — otomatis di-embed.')
                    ->visible(fn ($get) => $get('tipe') === 'video')
                    ->required(fn ($get) => $get('tipe') === 'video')
                    ->maxLength(500)
                    ->columnSpanFull(),

                FileUpload::make('path_file')
                    ->label('File PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    // Disk unggahan publik (MEDIA_DISK) — portabel ke R2 di produksi.
                    ->disk(config('media-library.disk_name'))
                    ->directory('modules/pages/pdf')
                    ->visibility('public')
                    ->visible(fn ($get) => $get('tipe') === 'pdf')
                    ->required(fn ($get) => $get('tipe') === 'pdf')
                    ->maxSize(10240) // 10 MB
                    ->helperText('Maksimal 10 MB, format PDF.')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('judul')
            ->reorderable('urutan')
            ->defaultSort('urutan', 'asc')
            ->columns([
                TextColumn::make('urutan')
                    ->label('#')
                    ->width('50px'),

                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Halaman'),
            ])
            ->recordActions([
                EditAction::make(),
                // Cegah modul published jadi tanpa materi (warga akan lihat modul kosong
                // & tak bisa menyelesaikannya). Turunkan ke draft dulu untuk mengosongkan.
                DeleteAction::make()
                    ->before(function (DeleteAction $action): void {
                        $module = $this->getOwnerRecord();

                        if ($module->status === ModuleStatus::Published && $module->pages()->count() <= 1) {
                            Notification::make()
                                ->title('Tidak bisa menghapus materi terakhir')
                                ->body('Modul ini sudah dipublish. Tambah materi lain dulu, atau ubah status modul ke Draft sebelum menghapus materi terakhir.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }
}
