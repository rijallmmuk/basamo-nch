<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Models\EwsDevice;
use App\Models\Nagari;
use App\Services\Ews\EwsPanelService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Pemantauan EWS banjir bandang (Pilar 4) di panel.
 *
 * Operator nagari otomatis ter-scope ke nagarinya; superadmin dan DPMD memilih
 * nagari lewat pemilih yang sama seperti halaman Cuaca. Polanya sengaja disamakan
 * supaya tidak ada dua cara berbeda memilih nagari di grup menu yang sama.
 *
 * Halaman ini BACA-SAJA. Pengelolaan perangkat dan tokennya bukan di sini: token
 * tidak boleh melewati layar yang dibuka banyak peran.
 *
 * @property-read Nagari|null $nagariTerpilih
 * @property-read array|null $panel
 */
class PemantauanEws extends Page
{
    use HasPanelBreadcrumbs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pemantauan EWS';

    protected string $view = 'filament.pages.pemantauan-ews';

    public ?int $nagariId = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Smart IoT';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd']);
    }

    public function getTitle(): string
    {
        return 'Pemantauan EWS';
    }

    public function mount(): void
    {
        $nagariDariUrl = request()->integer('nagari') ?: null;

        $this->nagariId = auth()->user()?->managedNagariId()
            ?? $nagariDariUrl
            // Dahulukan nagari yang MEMANG punya perangkat: membuka halaman
            // pemantauan lalu disambut nagari tanpa sensor hanya menyesatkan.
            ?? EwsDevice::query()->siapPakai()->value('nagari_id')
            ?? Nagari::query()->orderBy('nama')->value('id');
    }

    /** Nagari efektif — operator TIDAK bisa mengintip nagari lain lewat manipulasi state. */
    protected function nagariAktif(): ?int
    {
        $managed = auth()->user()?->managedNagariId();

        return $managed ?? ($this->nagariId ? (int) $this->nagariId : null);
    }

    /** Pilihan nagari — hanya peran lintas nagari. */
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

    /** @return array<string, mixed>|null */
    public function getPanelProperty(): ?array
    {
        $nagari = $this->nagariTerpilih;

        return $nagari ? app(EwsPanelService::class)->untukNagari($nagari) : null;
    }

    /** Ringkasan seluruh titik pantau — hanya untuk peran lintas nagari. */
    public function getSeluruhTitikProperty(): Collection
    {
        if (auth()->user()?->managedNagariId() !== null) {
            return collect();
        }

        return EwsDevice::query()
            ->siapPakai()
            ->with('nagari')
            ->get()
            ->map(fn (EwsDevice $device): array => app(EwsPanelService::class)->untukPerangkat($device, 6));
    }
}
