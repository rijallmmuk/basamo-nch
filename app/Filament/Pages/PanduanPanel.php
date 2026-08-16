<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Support\Panduan;
use Filament\Pages\Page;

/**
 * Panduan penggunaan panel bagi peran yang sudah punya, saat ini pengajar.
 *
 * Isinya dibaca dari berkas Markdown yang sama dengan sumber PDF-nya, jadi keduanya
 * tidak mungkin berbeda isi. Menu ini hilang sendiri bagi peran yang panduannya
 * belum ditulis.
 */
class PanduanPanel extends Page
{
    use HasPanelBreadcrumbs;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.panduan-panel';

    public static function getNavigationGroup(): ?string
    {
        return 'Bantuan';
    }

    public static function canAccess(): bool
    {
        return Panduan::peranUntuk(auth()->user()) !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        $peran = Panduan::peranUntuk(auth()->user());

        return $peran ? Panduan::judul($peran) : 'Panduan';
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function getPeran(): string
    {
        return Panduan::peranUntuk(auth()->user()) ?? '';
    }

    public function getIsi(): string
    {
        return Panduan::html($this->getPeran());
    }

    /** @return array<int, array{tingkat: int, id: string, teks: string}> */
    public function getDaftarIsi(): array
    {
        return Panduan::daftarIsi($this->getPeran());
    }
}
