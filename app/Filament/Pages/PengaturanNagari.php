<?php

namespace App\Filament\Pages;

use App\Models\Nagari;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Self-service admin nagari atas detail lokal desanya sendiri: sebutan sub-unit,
 * logo desa, kontak, koordinat. Identitas & atribut resmi (nama/jenis/wilayah
 * induk/logo kabupaten) tetap ranah super_admin via NagariResource.
 */
class PengaturanNagari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.pengaturan-nagari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?Nagari $nagari = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pengaturan Nagari';
    }

    public function getTitle(): string
    {
        return 'Pengaturan '.($this->nagari?->nama_lengkap ?? 'Nagari');
    }

    /** Hanya admin nagari; super_admin mengelola via NagariResource. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isNagariAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->nagari = auth()->user()->nagari;
        abort_unless($this->nagari, 403);

        $this->form->fill($this->nagari->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sebutan & Logo')
                    ->columns(2)
                    ->schema([
                        Select::make('wilayah_label')
                            ->label('Sebutan sub-unit')
                            ->options(Nagari::subUnitOptions())
                            ->searchable()
                            ->native(false)
                            ->helperText('Sebutan bagian dalam desa ini — mis. Jorong / Dusun / Lingkungan. Dipakai di seluruh aplikasi.'),

                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo '.($this->nagari?->jenis ?? 'desa'))
                            ->collection('logo')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Opsional. JPG/PNG/WEBP/SVG, maks 2 MB.'),
                    ]),

                Section::make('Kontak & Koordinat')
                    ->columns(2)
                    ->schema([
                        TextInput::make('kontak')
                            ->label('Kontak')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('koordinat_lat')
                            ->label('Lintang (lat)')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),

                        TextInput::make('koordinat_lng')
                            ->label('Bujur (lng)')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                    ]),
            ])
            ->statePath('data')
            ->model($this->nagari);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->nagari->update($data);
        $this->form->model($this->nagari)->saveRelationships();

        Notification::make()
            ->title('Pengaturan nagari tersimpan.')
            ->success()
            ->send();
    }
}
