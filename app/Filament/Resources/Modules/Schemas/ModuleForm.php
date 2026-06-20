<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Models\Module;
use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                    // Ubah nagari → segarkan opsi prasyarat (harus global/senagari).
                    ->live()
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
                    ->helperText('Publish hanya bisa setelah modul memiliki minimal satu materi.')
                    // Cegah modul kosong tampil ke warga: publish butuh ≥1 materi.
                    ->rule(fn (?Module $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                        if ($value === 'published' && (! $record || $record->pages()->doesntExist())) {
                            $fail('Tambahkan minimal satu materi sebelum modul dipublish.');
                        }
                    })
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
                        modifyQueryUsing: function ($query, ?Module $record, Get $get) {
                            // Tidak boleh menjadikan modul sebagai prasyarat dirinya sendiri.
                            if ($record) {
                                $query->whereKeyNot($record->getKey());
                            }

                            // Prasyarat hanya boleh modul GLOBAL atau SENAGARI dengan modul ini.
                            // Mencegah modul terkunci permanen bagi warga yang tak punya akses
                            // ke prasyarat lintas-nagari.
                            $nagariId = self::moduleNagariId($get);

                            $query->where(function ($q) use ($nagariId) {
                                $q->whereNull('nagari_id');
                                if ($nagariId) {
                                    $q->orWhere('nagari_id', $nagariId);
                                }
                            });
                        },
                    )
                    ->placeholder('— Tidak ada prasyarat —')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    // Guard server-side (otoritatif): tolak prasyarat lintas-nagari
                    // walau opsi dipaksa lewat request.
                    ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                        if (! $value) {
                            return;
                        }

                        $prerequisite = Module::find($value);

                        if ($prerequisite && $prerequisite->nagari_id !== null
                            && $prerequisite->nagari_id != self::moduleNagariId($get)) {
                            $fail('Prasyarat harus modul global atau dari nagari yang sama.');
                        }
                    })
                    ->columnSpan(1),

                RichEditor::make('description')
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

                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ])
            ->columns(2);
    }

    /**
     * Nagari efektif modul yang sedang disunting. nagari_admin: selalu nagarinya
     * (field nagari_id disembunyikan). super_admin: dari pilihan form (null = global).
     */
    protected static function moduleNagariId(Get $get): ?int
    {
        $user = auth()->user();

        if ($user?->isNagariAdmin()) {
            return $user->nagari_id;
        }

        $value = $get('nagari_id');

        return $value ? (int) $value : null;
    }
}
