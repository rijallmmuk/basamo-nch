<?php

namespace App\Filament\Resources\UmkmCategories;

use App\Filament\Resources\UmkmCategories\Pages\ManageUmkmCategories;
use App\Models\UmkmCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UmkmCategoryResource extends Resource
{
    protected static ?string $model = UmkmCategory::class;

    /**
     * Pilihan ikon kategori (Heroicon outline) — kurasi yang relevan untuk jenis
     * usaha desa; dropdown menampilkan ikonnya langsung sehingga super admin
     * tinggal memilih, dan nilai di luar daftar otomatis ditolak validasi Select.
     *
     * @var array<string, string> nama ikon => label pencarian
     */
    private const ICONS = [
        'heroicon-o-cake' => 'Kue / kuliner',
        'heroicon-o-fire' => 'Masakan / bakaran',
        'heroicon-o-sparkles' => 'Kerajinan',
        'heroicon-o-paint-brush' => 'Seni / lukis',
        'heroicon-o-scissors' => 'Jahit / potong',
        'heroicon-o-swatch' => 'Kain / fashion',
        'heroicon-o-shopping-bag' => 'Belanja / toko',
        'heroicon-o-building-storefront' => 'Warung / kios',
        'heroicon-o-sun' => 'Pertanian',
        'heroicon-o-beaker' => 'Peternakan / perikanan',
        'heroicon-o-bug-ant' => 'Serangga / lebah',
        'heroicon-o-briefcase' => 'Jasa / profesional',
        'heroicon-o-wrench-screwdriver' => 'Bengkel / perbaikan',
        'heroicon-o-truck' => 'Angkutan / kirim',
        'heroicon-o-home-modern' => 'Properti / bangunan',
        'heroicon-o-cpu-chip' => 'Elektronik',
        'heroicon-o-device-phone-mobile' => 'Ponsel / pulsa',
        'heroicon-o-camera' => 'Foto / dokumentasi',
        'heroicon-o-musical-note' => 'Musik / hiburan',
        'heroicon-o-book-open' => 'Buku / pendidikan',
        'heroicon-o-heart' => 'Kesehatan / kecantikan',
        'heroicon-o-gift' => 'Suvenir / hadiah',
        'heroicon-o-cube' => 'Produk umum',
        'heroicon-o-ellipsis-horizontal-circle' => 'Lainnya',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Taksonomi global lintas-desa → hanya super admin (cegah satu desa mengubah
    // kategori yang dipakai semua desa).
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getModelLabel(): string
    {
        return 'Kategori UMKM';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori UMKM';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Kategori')
                ->icon(Heroicon::OutlinedTag)
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama kategori')
                        ->required()
                        ->maxLength(100)
                        // Konsisten dgn Data Master: nama referensi global unik.
                        ->unique(ignoreRecord: true),

                    Select::make('icon')
                        ->label('Ikon')
                        ->options(
                            collect(self::ICONS)->mapWithKeys(fn (string $label, string $name): array => [
                                $name => svg($name, 'inline-block h-5 w-5 align-middle')->toHtml()
                                    .'<span style="margin-left:.5rem">'.e($label).'</span>',
                            ])->all()
                        )
                        ->allowHtml()
                        ->searchable()
                        ->native(false)
                        ->placeholder('— Pilih ikon —')
                        ->helperText('Opsional — penanda visual kategori.'),

                    Textarea::make('panduan_produk')
                        ->label('Panduan deskripsi produk')
                        ->helperText('Satu poin per baris, cukup 3 poin umum (mis. "Berat atau isi per kemasan") — detail lanjutan biar ditanyakan pembeli lewat WhatsApp. Tampil di form produk warga kategori ini.')
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('contoh_deskripsi')
                        ->label('Contoh deskripsi produk')
                        ->helperText('Contoh deskripsi jadi (2–3 kalimat) khas kategori ini — warga bisa memakainya sekali klik lalu tinggal mengganti kata-katanya.')
                        ->rows(4)
                        ->columnSpanFull(),

                    // Urutan TIDAK diisi lewat form: kategori baru otomatis di urutan
                    // terakhir; mengubah urutan = seret baris di tabel.
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->toggleable(),

                IconColumn::make('icon')
                    ->label('Ikon')
                    ->icon(fn (?string $state): ?string => $state)
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('profiles_count')
                    ->label('UMKM')
                    ->counts('profiles')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->recordActions([
                // Kuning = konvensi aksi Ubah yang tampil langsung (sama dgn Wilayah & Data Master).
                EditAction::make()
                    ->color('warning'),
                // Kategori terpakai tidak boleh dihapus diam-diam (FK SET NULL akan
                // melepas kategori dari UMKM tanpa jejak) — konsisten dgn Data Master.
                DeleteAction::make()
                    ->before(function (UmkmCategory $record, DeleteAction $action): void {
                        $count = $record->profiles()->count();

                        if ($count > 0) {
                            Notification::make()
                                ->title('Tidak bisa dihapus — masih dipakai')
                                ->body("Masih dipakai {$count} UMKM. Pindahkan kategorinya dulu sebelum menghapus.")
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUmkmCategories::route('/'),
        ];
    }
}
