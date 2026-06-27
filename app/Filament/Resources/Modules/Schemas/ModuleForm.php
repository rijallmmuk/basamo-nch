<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Enums\ModuleStatus;
use App\Models\Module;
use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Modul')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('judul')
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

                        // Slug ditetapkan model (Spatie HasSlug, unik per desa + auto-suffix).
                        // Field ini hanya pratinjau read-only; tak dikirim ke server.
                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->helperText('Otomatis dari judul. Bila bentrok di desa yang sama, ditambah akhiran (mis. -1).')
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Select::make('desa_id')
                            ->label('Desa')
                            ->relationship('desa', 'nama')
                            ->placeholder('— Semua desa —')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            // Ubah desa → segarkan opsi prasyarat (harus global/sedesa).
                            ->live()
                            // Hanya super_admin yang menentukan desa/global.
                            // desa_admin: desa_id diisi otomatis (lihat CreateModule).
                            ->visible(fn () => auth()->user()?->isSuperAdmin())
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Status')
                            ->options(ModuleStatus::class)
                            ->default('draft')
                            ->required()
                            ->helperText('Publish hanya bisa setelah modul memiliki minimal satu materi.')
                            // Cegah modul kosong tampil ke warga: publish butuh ≥1 materi.
                            ->rule(fn (?Module $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                if ($value === 'published' && (! $record || $record->pages()->doesntExist())) {
                                    $fail('Tambahkan minimal satu materi sebelum modul dipublish.');
                                }
                            })
                            ->columnSpan(1),

                        TextInput::make('estimasi_menit')
                            ->label('Estimasi Durasi')
                            ->helperText('Perkiraan lama belajar modul ini. Opsional.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->suffix('menit')
                            ->nullable()
                            ->columnSpan(1),

                        Select::make('prasyarat_module_id')
                            ->label('Prasyarat Modul')
                            ->relationship(
                                name: 'prerequisite',
                                titleAttribute: 'judul',
                                modifyQueryUsing: function ($query, ?Module $record, Get $get) {
                                    // Tidak boleh menjadikan modul sebagai prasyarat dirinya sendiri.
                                    if ($record) {
                                        $query->whereKeyNot($record->getKey());
                                    }

                                    // Prasyarat hanya boleh modul GLOBAL atau SEDESA dengan modul ini.
                                    // Mencegah modul terkunci permanen bagi warga yang tak punya akses
                                    // ke prasyarat lintas-desa.
                                    $desaId = self::moduleDesaId($get);

                                    $query->where(function ($q) use ($desaId) {
                                        $q->whereNull('desa_id');
                                        if ($desaId) {
                                            $q->orWhere('desa_id', $desaId);
                                        }
                                    });
                                },
                            )
                            ->placeholder('— Tidak ada prasyarat —')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            // Guard server-side (otoritatif): tolak prasyarat lintas-desa
                            // walau opsi dipaksa lewat request.
                            ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value) {
                                    return;
                                }

                                $prerequisite = Module::find($value);

                                if ($prerequisite && $prerequisite->desa_id !== null
                                    && $prerequisite->desa_id != self::moduleDesaId($get)) {
                                    $fail('Prasyarat harus modul global atau dari desa yang sama.');
                                }
                            })
                            ->columnSpan(1),

                        Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ]),

                Section::make('Deskripsi & Cover')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('deskripsi')
                            ->label('Deskripsi')
                            ->nullable()
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('cover')
                            ->label('Cover Modul')
                            ->helperText('Opsional. Bila kosong, dipakai cover default. Disarankan rasio 16:9.')
                            ->collection('cover')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9'])
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Desa efektif modul yang sedang disunting. desa_admin: selalu desanya
     * (field desa_id disembunyikan). super_admin: dari pilihan form (null = global).
     */
    protected static function moduleDesaId(Get $get): ?int
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            return $user->desa_id;
        }

        $value = $get('desa_id');

        return $value ? (int) $value : null;
    }
}
