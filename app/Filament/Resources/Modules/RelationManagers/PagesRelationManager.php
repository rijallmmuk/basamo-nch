<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\ModuleBlockType;
use App\Enums\ModuleStatus;
use App\Models\ModulePage;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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

                Builder::make('blocks')
                    ->label('Isi Materi')
                    ->helperText('Susun materi dari blok-blok. Satu halaman bisa mencampur teks, video, PDF, gambar, audio, & lampiran — seret untuk mengurutkan.')
                    ->blocks(self::blocks())
                    ->addActionLabel('Tambah blok')
                    ->collapsible()
                    ->blockNumbers(false)
                    ->minItems(1)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Definisi blok konten. Berkas tersimpan di disk media (portabel ke R2) di
     * direktori per-tipe; pembersihan berkas yatim ditangani model ModulePage.
     *
     * @return array<int, Block>
     */
    private static function blocks(): array
    {
        $disk = config('media-library.disk_name');

        return [
            Block::make('teks')
                ->label('Teks')
                ->icon(ModuleBlockType::Teks->getIcon())
                ->schema([
                    RichEditor::make('konten')
                        ->label('Teks')
                        ->required()
                        ->columnSpanFull(),
                ]),

            Block::make('video')
                ->label('Video')
                ->icon(ModuleBlockType::Video->getIcon())
                ->schema([
                    TextInput::make('url')
                        ->label('URL Video')
                        ->url()
                        ->required()
                        ->rule(self::httpOnlyRule())
                        ->placeholder('https://www.youtube.com/watch?v=...')
                        ->helperText('Tempel link YouTube atau Google Drive biasa — otomatis di-embed.')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Keterangan (opsional)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('pdf')
                ->label('PDF / Dokumen')
                ->icon(ModuleBlockType::Pdf->getIcon())
                ->schema([
                    FileUpload::make('file')
                        ->label('File PDF')
                        ->acceptedFileTypes(['application/pdf'])
                        ->disk($disk)
                        ->directory('modules/blocks/pdf')
                        ->visibility('public')
                        ->required()
                        ->maxSize(10240)
                        ->helperText('Maksimal 10 MB, format PDF.')
                        ->columnSpanFull(),
                    TextInput::make('judul')
                        ->label('Judul dokumen (opsional)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('gambar')
                ->label('Gambar')
                ->icon(ModuleBlockType::Gambar->getIcon())
                ->schema([
                    FileUpload::make('file')
                        ->label('Gambar')
                        ->image()
                        ->disk($disk)
                        ->directory('modules/blocks/gambar')
                        ->visibility('public')
                        ->required()
                        ->maxSize(4096)
                        ->helperText('Maksimal 4 MB (JPG/PNG/WebP).')
                        ->columnSpanFull(),
                    TextInput::make('alt')
                        ->label('Teks alternatif (aksesibilitas)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Keterangan (opsional)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('audio')
                ->label('Audio')
                ->icon(ModuleBlockType::Audio->getIcon())
                ->schema([
                    FileUpload::make('file')
                        ->label('File Audio')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/x-wav', 'audio/ogg'])
                        ->disk($disk)
                        ->directory('modules/blocks/audio')
                        ->visibility('public')
                        ->required()
                        ->maxSize(20480)
                        ->helperText('Maksimal 20 MB (MP3/M4A/WAV/OGG).')
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Keterangan (opsional)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('lampiran')
                ->label('Lampiran')
                ->icon(ModuleBlockType::Lampiran->getIcon())
                ->schema([
                    FileUpload::make('file')
                        ->label('Berkas')
                        ->disk($disk)
                        ->directory('modules/blocks/lampiran')
                        ->visibility('public')
                        ->required()
                        ->maxSize(20480)
                        ->helperText('Maksimal 20 MB. Berkas yang bisa diunduh warga (mis. DOCX, slide, gambar).')
                        ->columnSpanFull(),
                    TextInput::make('label')
                        ->label('Nama tampilan (opsional)')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** Hanya skema http/https (cegah javascript:/data: yang dirender di tautan). */
    private static function httpOnlyRule(): Closure
    {
        return static function (): Closure {
            return function (string $attribute, $value, Closure $fail): void {
                if (filled($value) && ! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    $fail('URL video harus diawali http:// atau https://.');
                }
            };
        };
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
                    ->width('50px')
                    ->alignCenter(),

                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),

                // Ringkasan isi: badge per tipe blok yang dipakai halaman ini.
                TextColumn::make('blocks')
                    ->label('Isi')
                    ->badge()
                    ->state(fn (ModulePage $record): array => collect($record->blocks ?? [])
                        ->map(fn ($block) => ModuleBlockType::tryFrom($block['type'] ?? '')?->getLabel())
                        ->filter()
                        ->countBy()
                        ->map(fn (int $count, string $label) => $count > 1 ? "{$label} ×{$count}" : $label)
                        ->values()
                        ->all())
                    ->placeholder('—'),
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
