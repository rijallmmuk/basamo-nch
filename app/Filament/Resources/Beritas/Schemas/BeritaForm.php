<?php

namespace App\Filament\Resources\Beritas\Schemas;

use App\Enums\KategoriBerita;
use App\Enums\StatusBerita;
use App\Filament\Forms\Components\OptimizedSpatieMediaLibraryFileUpload;
use App\Models\Berita;
use App\Models\Nagari;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class BeritaForm
{
    public static function configure(Schema $schema): Schema
    {
        $actor = auth()->user();
        $isOperator = $actor?->isOperator() ?? false;

        return $schema
            ->columns(1)
            ->components([
                Section::make('Konten Berita & Pengumuman')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description('Tulis judul, kategori, ringkasan, dan isi lengkap publikasi.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('judul')
                            ->label('Judul Berita / Pengumuman')
                            ->placeholder('Masukkan judul berita yang jelas dan informatif')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (mixed $state, callable $set, string $operation) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->placeholder('slug-berita-otomatis')
                            ->required()
                            ->maxLength(255)
                            ->unique(Berita::class, 'slug', ignoreRecord: true)
                            ->helperText('Digunakan untuk tautan publik halaman berita.')
                            ->columnSpanFull(),

                        Select::make('kategori')
                            ->label('Kategori')
                            ->options(KategoriBerita::class)
                            ->default(KategoriBerita::Berita->value)
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label('Status Publikasi')
                            ->options(StatusBerita::class)
                            ->default(StatusBerita::Diterbitkan->value)
                            ->required()
                            ->native(false),

                        Textarea::make('ringkasan')
                            ->label('Ringkasan / Cuplikan')
                            ->placeholder('Tulis ringkasan singkat 1-2 kalimat untuk kartu pratinjau dan SEO...')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Maksimal 500 karakter. Jika dikosongkan, cuplikan akan diambil dari isi konten.')
                            ->columnSpanFull(),

                        RichEditor::make('konten')
                            ->label('Isi Lengkap')
                            ->placeholder('Tulis isi lengkap berita, pengumuman, atau agenda kegiatan...')
                            ->fileAttachmentsDisk(config('media-library.disk_name'))
                            ->fileAttachmentsDirectory('berita/konten')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Media & Lampiran')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->description('Foto sampul utama dan berkas dokumen pendukung.')
                    ->columns(1)
                    ->schema([
                        OptimizedSpatieMediaLibraryFileUpload::make('sampul')
                            ->label('Foto Sampul Utama')
                            ->collection('sampul')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageEditorAspectRatios(['16:9', '4:3'])
                            ->helperText('Format JPG, PNG, atau WebP. Disarankan rasio 16:9 untuk tampilan optimal di kartu dan hero berita.')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('lampiran')
                            ->label('Berkas Lampiran Dokumen (Opsional)')
                            ->collection('lampiran')
                            ->multiple()
                            ->reorderable()
                            ->maxSize(20480)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->helperText('PDF, Word, Excel, atau gambar dokumen pendukung (SK, edaran, formulir). Maksimal 20 MB per berkas.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan Publikasi & Sasaran')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->description('Pengaturan jadwal tayang, penanda sorotan utama, dan audiens nagari.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_pinned')
                            ->label('Sematkan sebagai Sorotan Utama (Pinned)')
                            ->helperText('Berita yang disematkan tampil di posisi teratas sebagai sorotan.')
                            ->default(false),

                        DateTimePicker::make('published_at')
                            ->label('Waktu Terbit')
                            ->default(now())
                            ->native(false)
                            ->seconds(false)
                            ->helperText('Berita hanya akan tampil di publik setelah waktu ini tercapai.'),

                        TextInput::make('penulis_nama')
                            ->label('Nama Penulis / Sumber Berita')
                            ->placeholder('Contoh: Tim Liputan / Dinas Kominfo / Wali Nagari')
                            ->maxLength(100)
                            ->helperText('Kosongkan untuk menggunakan nama akun pembuat secara otomatis.')
                            ->columnSpanFull(),

                        // Sasaran Nagari
                        ...($isOperator ? [
                            TextInput::make('nagari_label')
                                ->label('Nagari Pemilik')
                                ->default($actor?->nagari?->nama ?? 'Nagari Terdaftar')
                                ->readOnly()
                                ->dehydrated(false)
                                ->helperText('Berita yang dibuat operator secara otomatis ditujukan untuk nagari bersangkutan.')
                                ->columnSpanFull(),
                        ] : [
                            Toggle::make('semua_nagari')
                                ->label('Berlaku untuk seluruh nagari')
                                ->helperText('Jika aktif, berita akan ditampilkan di seluruh portal nagari dan situs utama.')
                                ->default(true)
                                ->live()
                                ->columnSpanFull(),

                            Select::make('sasaran')
                                ->label('Pilih Nagari Sasaran')
                                ->multiple()
                                ->options(fn (): array => Nagari::query()->orderBy('nama')->pluck('nama', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->prefixIcon(Heroicon::OutlinedMapPin)
                                ->placeholder('Pilih satu atau lebih nagari sasaran')
                                ->required(fn ($get): bool => ! (bool) $get('semua_nagari'))
                                ->visible(fn ($get): bool => ! (bool) $get('semua_nagari'))
                                ->dehydrated(false)
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
