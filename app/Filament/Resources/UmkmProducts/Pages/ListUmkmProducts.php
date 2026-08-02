<?php

namespace App\Filament\Resources\UmkmProducts\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\UmkmProducts\UmkmProductResource;
use App\Models\UmkmProduct;
use App\Support\NagariContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListUmkmProducts extends ListRecords
{
    protected static string $resource = UmkmProductResource::class;

    use HasListTitle;

    // Super admin selalu tampil di sidebar (2026-07-14) — tanpa konteks, otomatis
    // ke nagari pertama (urut nama) supaya tak buntu.
    public ?int $nagariId = null;

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()?->hasAnyRole(['superadmin', 'dpmd']) ?? false) {
            NagariContext::ensureDefault(NagariContext::UMKM_PRODUK);
            $this->nagariId = NagariContext::id(NagariContext::UMKM_PRODUK);
        }
    }

    // Pemilih nagari inline (2026-07-14, setara SDGs/Cuaca — koreksi dari pola lama
    // "auto nagari pertama + tombol Kembali ke Nagari"): ganti nagariId → NagariContext
    // (namespace UMKM_PRODUK, independen dari menu lain) ikut disetel, tabel
    // (query statis resource, baca NagariContext) langsung ter-render ulang, tanpa reload.
    public function updatedNagariId(): void
    {
        if ((auth()->user()?->hasAnyRole(['superadmin', 'dpmd']) ?? false) && $this->nagariId !== null) {
            NagariContext::set(NagariContext::UMKM_PRODUK, $this->nagariId);
        }
    }

    public function content(Schema $schema): Schema
    {
        if (! (auth()->user()?->hasAnyRole(['superadmin', 'dpmd']) ?? false)) {
            return parent::content($schema);
        }

        $components = parent::content($schema)->getComponents();
        array_splice($components, 1, 0, [View::make('filament.components.nagari-picker-banner')]);

        return $schema->components($components);
    }

    /**
     * Buat produk lewat HALAMAN tersendiri, bukan modal: formulirnya dua bagian plus
     * pengunggah lima foto, dan foto justru yang paling menentukan tampilan produk di
     * etalase. Isinya pindah ke CreateUmkmProduct.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('tambahProduk')->color('primary')
                ->label('Tambah Produk')
                ->authorize(fn (): bool => auth()->user()?->can('create', UmkmProduct::class) ?? false)
                ->visible(fn (): bool => CreateUmkmProduct::lapakOptions() !== [])
                ->url(fn (): string => UmkmProductResource::getUrl('create')),
        ];
    }
}
