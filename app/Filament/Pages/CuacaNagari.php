<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Models\Nagari;
use App\Services\BmkgWeatherService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Prakiraan cuaca BMKG nagari (sama sumber & cache dgn halaman publik /cuaca) —
 * operator nagari otomatis ter-scope ke nagarinya; superadmin memilih nagari (pola
 * sama dgn halaman Capaian SDGs, lihat ListSdgAchievements).
 *
 * @property-read Nagari|null $nagariTerpilih
 */
class CuacaNagari extends Page
{
    use HasPanelBreadcrumbs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Cuaca';

    // Grup "Smart IoT" dipertahankan meski lapisan data sensor sudah dibuang
    // (2026-07-29): Pilar 4 tetap hidup sebagai wajah publik "Coming Soon", dan
    // Cuaca BMKG adalah satu-satunya sumber data lingkungan yang sudah nyata.
    public static function getNavigationGroup(): ?string
    {
        return 'Smart IoT';
    }

    protected string $view = 'filament.pages.cuaca-nagari';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd']);
    }

    public ?int $nagariId = null;

    public function getTitle(): string
    {
        return 'Cuaca';
    }

    public function mount(): void
    {
        // ?nagari= dari aksi "Kelola Cuaca" di daftar Nagari (super admin) — pra-pilih
        // tanpa NagariContext, picker tetap muncul agar bisa ganti nagari lain.
        $nagariDariUrl = request()->integer('nagari') ?: null;

        $this->nagariId = auth()->user()?->managedNagariId()
            ?? $nagariDariUrl
            ?? Nagari::query()->orderBy('nama')->value('id');
    }

    /** Nagari efektif — operator nagari TIDAK bisa mengintip nagari lain via manipulasi state. */
    protected function nagariAktif(): ?int
    {
        $managed = auth()->user()?->managedNagariId();

        return $managed ?? ($this->nagariId ? (int) $this->nagariId : null);
    }

    /** Pilihan nagari — hanya super admin. */
    public function getPilihanNagariProperty(): Collection
    {
        if (auth()->user()?->managedNagariId() !== null) {
            return collect();
        }

        return Nagari::query()->orderBy('nama')->get();
    }

    public function getNagariTerpilihProperty(): ?Nagari
    {
        $id = $this->nagariAktif();

        return $id !== null ? Nagari::find($id) : null;
    }

    /** @return array{lokasi: ?string, saat_ini: ?array, hari: array<int, array{tanggal: string, slots: array}>}|null */
    public function getCuacaProperty(): ?array
    {
        $nagari = $this->nagariTerpilih;

        return $nagari ? app(BmkgWeatherService::class)->prakiraan($nagari) : null;
    }
}
