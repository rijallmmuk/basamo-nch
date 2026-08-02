<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\ModuleBlockType;
use App\Filament\Forms\Components\OptimizedImageUpload;
use App\Support\BlockFilePath;
use App\Filament\Resources\Modules\Pages\CreateMateris;
use App\Models\Materi;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterisRelationManager extends RelationManager
{
    /**
     * Batas ukuran per berkas materi (KB). Materi pelatihan kerap berupa modul PDF
     * bergambar atau rekaman penjelasan yang panjang, jadi batasnya lebih longgar
     * daripada foto produk UMKM.
     *
     * Server WAJIB mengizinkan setidaknya sebesar ini pada `upload_max_filesize`,
     * `post_max_size`, dan `client_max_body_size` (lihat DEPLOY.md).
     */
    public const MAX_UKURAN_KB = 20480;

    protected static string $relationship = 'materis';

    protected static ?string $title = 'Halaman Materi';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->trashed()
            || ! (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(self::fields());
    }

    /** @return array<int, Component> */
    public static function fields(): array
    {
        return [
            TextInput::make('judul')
                ->label('Judul Materi')
                ->placeholder('Isi judul materi')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Builder::make('blocks')
                ->label('Isi Materi')
                ->blocks(self::blocks())
                ->addActionLabel('Tambah blok')
                ->collapsible()
                ->blockNumbers(false)
                ->minItems(1)
                ->required()
                ->columnSpanFull(),
        ];
    }

    /**
     * Definisi blok konten. Berkas tersimpan di disk media (portabel ke R2) di
     * direktori per-tipe; pembersihan berkas yatim ditangani model Materi.
     *
     * @return array<int, Block>
     */
    private static function blocks(): array
    {
        $disk = config('slc.material_disk');

        return [
            Block::make('teks')
                ->label('Teks')
                ->icon(ModuleBlockType::Teks->getIcon())
                ->schema([
                    RichEditor::make('konten')
                        ->label('Teks')
                        ->placeholder('Isi teks materi')
                        ->extraInputAttributes(['class' => 'slc-resizable-rich-editor'])
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
                        ->placeholder('Tempel tautan video')
                        ->helperText('Tautan YouTube atau Google Drive, jangan yang private.')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Keterangan')
                        ->placeholder('Isi keterangan video')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('pdf')
                ->label('PDF')
                ->icon(ModuleBlockType::Pdf->getIcon())
                ->schema([
                    FileUpload::make('file')
                        ->label('File PDF')
                        ->acceptedFileTypes(['application/pdf'])
                        ->disk($disk)
                        ->directory('modules/blocks/pdf')
                        ->visibility('private')
                        ->preventFilePathTampering(allowFilePathUsing: BlockFilePath::allow($disk, 'modules/blocks/pdf'))
                        // TANPA kompresi. Sebelumnya PDF dilewatkan Ghostscript saat
                        // menyimpan, dan itu berjalan di dalam request: satu materi
                        // berisi beberapa PDF bisa menahan penyimpanan sampai menitan,
                        // melewati batas waktu proxy, sehingga berkasnya baru muncul
                        // jauh setelah pengajar mengira penyimpanan gagal. Hemat
                        // penyimpanan tidak sebanding dengan itu.
                        ->required()
                        ->maxSize(self::MAX_UKURAN_KB)
                        ->helperText('Hanya PDF, maksimal 20 MB.')
                        ->columnSpanFull(),
                    TextInput::make('judul')
                        ->label('Judul Dokumen')
                        ->placeholder('Isi judul dokumen')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('gambar')
                ->label('Gambar')
                ->icon(ModuleBlockType::Gambar->getIcon())
                ->schema([
                    OptimizedImageUpload::make('file')
                        ->label('Gambar')
                        ->optimizeTo($disk, 'modules/blocks/gambar')
                        ->required()
                        ->maxSize(self::MAX_UKURAN_KB)
                        ->helperText('JPG, PNG, atau WEBP, maksimal 20 MB.')
                        ->columnSpanFull(),
                    TextInput::make('alt')
                        ->label('Teks Alternatif')
                        ->placeholder('Isi teks alternatif')
                        ->helperText('Membantu warga yang menggunakan pembaca layar.')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Keterangan')
                        ->placeholder('Isi keterangan gambar')
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
                        ->visibility('private')
                        ->preventFilePathTampering(allowFilePathUsing: BlockFilePath::allow($disk, 'modules/blocks/audio'))
                        ->required()
                        ->maxSize(self::MAX_UKURAN_KB)
                        ->helperText('MP3, M4A, WAV, atau OGG, maksimal 20 MB.')
                        ->columnSpanFull(),
                    TextInput::make('caption')
                        ->label('Keterangan')
                        ->placeholder('Isi keterangan audio')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Block::make('lampiran')
                ->label('Lampiran')
                ->icon(ModuleBlockType::Lampiran->getIcon())
                ->schema([
                    FileUpload::make('file')
                        ->label('Berkas')
                        // Whitelist tipe aman (dokumen/arsip/gambar) — cegah unggahan
                        // HTML/SVG/skrip yang bisa jadi vektor XSS saat dibuka warga.
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                            'application/zip', 'application/x-7z-compressed', 'application/x-rar-compressed',
                            'text/plain', 'text/csv',
                        ])
                        ->disk($disk)
                        ->directory('modules/blocks/lampiran')
                        ->visibility('private')
                        ->preventFilePathTampering(allowFilePathUsing: BlockFilePath::allow($disk, 'modules/blocks/lampiran'))
                        ->required()
                        ->maxSize(self::MAX_UKURAN_KB)
                        ->helperText('Berkas unduhan, termasuk PowerPoint dan Word. Maksimal 20 MB.')
                        ->columnSpanFull(),
                    TextInput::make('label')
                        ->label('Nama Tampilan')
                        ->placeholder('Isi nama tampilan berkas')
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
                    ->state(fn (Materi $record): array => collect($record->blocks ?? [])
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
                Action::make('create')
                    ->color('primary')
                    ->label('Tambah Materi')
                    ->authorize(fn (): bool => ! $this->getOwnerRecord()->trashed()
                        && (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false))
                    ->url(fn (): string => CreateMateris::getUrl([
                        'record' => $this->getOwnerRecord(),
                    ])),
            ])
            ->recordActions([
                // Aksi dikumpulkan dalam satu menu ⋮ (konvensi sama dgn tabel Warga).
                ActionGroup::make([
                    EditAction::make()
                        ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)
                        ->color('warning'),
                    DeleteAction::make()
                        ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ]);
    }
}
