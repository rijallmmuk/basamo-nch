<?php

namespace App\Filament\Pages;

use App\Models\Desa;
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
 * Self-service admin desa atas detail lokal desanya sendiri: sebutan sub-unit,
 * logo desa, kontak, koordinat. Identitas & atribut resmi (nama/jenis/wilayah
 * induk/logo kabupaten) tetap ranah super_admin via DesaResource.
 */
class PengaturanDesa extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.pengaturan-desa';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?Desa $desa = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pengaturan Desa';
    }

    public function getTitle(): string
    {
        return 'Pengaturan '.($this->desa?->nama_lengkap ?? 'Desa');
    }

    /** Hanya admin desa; super_admin mengelola via DesaResource. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isDesaAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->desa = auth()->user()->desa;
        abort_unless($this->desa, 403);

        $this->form->fill($this->desa->attributesToArray());
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
                            ->options(Desa::subUnitOptions())
                            ->searchable()
                            ->native(false)
                            ->helperText('Sebutan bagian dalam desa ini — mis. Jorong / Dusun / Lingkungan. Dipakai di seluruh aplikasi.'),

                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo '.($this->desa?->jenis ?? 'desa'))
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
            ->model($this->desa);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->desa->update($data);
        $this->form->model($this->desa)->saveRelationships();

        Notification::make()
            ->title('Pengaturan desa tersimpan.')
            ->success()
            ->send();
    }
}
