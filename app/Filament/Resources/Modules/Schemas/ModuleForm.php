<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Models\Module;
use App\Models\Pelatihan;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use App\Filament\Forms\Components\OptimizedSpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Form modul. Datang dari halaman detail pelatihan lewat tombol "Tambah Modul",
 * sehingga pelatihannya sudah terisi dan terkunci: pengguna tidak memilih ulang
 * tema, pengajar, dan sasaran.
 */
class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Modul')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->description('Materi, Pre-test, dan Evaluasi Kegiatan ditambahkan setelah modul disimpan. Modul baru tampil ke warga setelah berisi minimal satu materi.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        // Slug URL dibuat & dijaga unik OTOMATIS oleh model (Spatie HasSlug),
                        // tak ditampilkan agar pengguna tak perlu memikirkannya.
                        TextInput::make('judul')
                            ->label('Judul Modul')
                            ->placeholder('Isi judul modul')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('pelatihan_id')
                            ->label('Pelatihan')
                            ->placeholder('Pilih pelatihan')
                            ->relationship(
                                name: 'pelatihan',
                                titleAttribute: 'id',
                                modifyQueryUsing: function (EloquentBuilder $query): EloquentBuilder {
                                    $actor = auth()->user();

                                    return $actor
                                        ? $query->manageableBy($actor)->with(['tema', 'nagaris'])
                                        : $query->whereKey([]);
                                },
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->namaTampil())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('prasyarat_module_id', null))
                            ->disabled(fn ($livewire): bool => property_exists($livewire, 'lockedPelatihanId')
                                && $livewire->lockedPelatihanId !== null)
                            // Terkunci saat datang lewat tombol "Tambah Modul" pada satu
                            // pelatihan; tanpa keterangan, isian abu-abu ini membingungkan.
                            ->helperText(fn ($livewire): ?string => property_exists($livewire, 'lockedPelatihanId')
                                && $livewire->lockedPelatihanId !== null
                                    ? 'Terkunci karena modul ini ditambahkan dari halaman pelatihan tersebut.'
                                    : null)
                            ->dehydrated()
                            ->rule(fn (?Module $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                $actor = auth()->user();

                                if (! $actor) {
                                    return;
                                }

                                if (! $value) {
                                    $fail('Pelatihan wajib dipilih.');

                                    return;
                                }

                                $pelatihan = Pelatihan::query()->manageableBy($actor)->find($value);

                                if (! $pelatihan) {
                                    $fail('Pelatihan tidak valid atau berada di luar kewenangan Anda.');

                                    return;
                                }

                                // Prasyarat di-scope per pelaksanaan. Memindahkan modul akan
                                // meyatimkan modul di pelaksanaan lama yang menjadikannya
                                // prasyarat → tolak sampai dependennya dipindah dulu.
                                if ($record
                                    && Module::query()
                                        ->where('prasyarat_module_id', $record->getKey())
                                        ->where('pelatihan_id', '!=', $pelatihan->getKey())
                                        ->exists()) {
                                    $fail('Modul ini masih menjadi prasyarat modul di pelatihan lain. Pindahkan dependennya terlebih dahulu.');
                                }
                            })
                            ->columnSpan(1),

                        Select::make('prasyarat_module_id')
                            ->label('Prasyarat Modul')
                            ->options(fn (Get $get, $livewire, ?Module $record): array => self::prerequisiteOptions(
                                $get,
                                $livewire,
                                $record,
                            ))
                            ->placeholder('Tanpa prasyarat')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->helperText('Pilihan hanya memuat modul lain dari pelatihan yang sama. Warga harus menuntaskannya sebelum modul ini terbuka.')
                            ->columnSpan(1),

                        /* Modul tidak menyumbang satu pun isian ke badan sertifikat; yang
                           dipengaruhinya adalah SYARAT terbitnya. Tanpa keterangan ini
                           pengajar mudah mengira modul yang belum berisi materi tetap
                           menahan sertifikat, padahal ia dilewati sama sekali. */
                        Callout::make()
                            ->heading('Pengaruh modul ini terhadap sertifikat')
                            ->description('Tidak ada isian di halaman ini yang tercetak di sertifikat. Yang ditentukan modul adalah syaratnya: warga baru berhak setelah seluruh modul berisi materi dituntaskan dan seluruh Evaluasi Kegiatan pada modul itu lulus. Modul yang belum berisi materi tidak ikut dihitung, sedangkan menambah modul baru menaikkan syarat bagi warga yang belum tuntas dan tidak mencabut sertifikat yang sudah terbit.')
                            ->icon(Heroicon::OutlinedDocumentCheck)
                            ->color('info')
                            ->visible(fn (Get $get, $livewire, ?Module $record): bool => self::pelatihanBersertifikat(
                                $get,
                                $livewire,
                                $record,
                            ))
                            ->columnSpanFull(),
                    ]),

                Section::make('Deskripsi Modul')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description('Opsional. Tulis ringkasan singkat isi modul.')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('Isi deskripsi modul')
                            ->extraInputAttributes(['class' => 'slc-resizable-rich-editor'])
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Cover Modul')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->description('Opsional. Tanpa unggahan, sistem menggunakan cover bawaan.')
                    ->columnSpanFull()
                    ->schema([
                        OptimizedSpatieMediaLibraryFileUpload::make('cover')
                            ->label('Cover Modul')
                            ->collection('cover')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('4:3')
                            ->imageEditorAspectRatios(['4:3', '16:9'])
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('900')
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, string> */
    protected static function prerequisiteOptions(Get $get, mixed $livewire, ?Module $record): array
    {
        $actor = auth()->user();
        $pelatihanId = self::modulePelatihanId($get, $livewire, $record);

        if (! $actor || ! $pelatihanId) {
            return [];
        }

        return Module::query()
            ->manageableBy($actor)
            ->where('pelatihan_id', $pelatihanId)
            ->when($record, fn (EloquentBuilder $query) => $query->whereKeyNot($record->getKey()))
            ->orderBy('urutan')
            ->orderBy('id')
            ->pluck('judul', 'id')
            ->all();
    }

    /** Pelaksanaan aktif: state form → konteks create terkunci → record edit. */
    /** Pelatihan induk modul ini memberi sertifikat, entah diterbitkan sistem atau diunggah? */
    protected static function pelatihanBersertifikat(Get $get, mixed $livewire, ?Module $record): bool
    {
        $pelatihanId = self::modulePelatihanId($get, $livewire, $record);

        if (! $pelatihanId) {
            return false;
        }

        return Pelatihan::query()
            ->whereKey($pelatihanId)
            ->value('sertifikat_mode')
            ?->memberiSertifikat() ?? false;
    }

    protected static function modulePelatihanId(Get $get, mixed $livewire, ?Module $record): ?int
    {
        $pelatihanId = $get('pelatihan_id')
            ?: ($livewire->lockedPelatihanId ?? null)
            ?: $record?->pelatihan_id;

        return $pelatihanId ? (int) $pelatihanId : null;
    }
}
