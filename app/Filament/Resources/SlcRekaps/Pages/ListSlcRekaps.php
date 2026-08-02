<?php

namespace App\Filament\Resources\SlcRekaps\Pages;

use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Support\NagariContext;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListSlcRekaps extends ListRecords
{
    protected static string $resource = SlcRekapResource::class;

    use HasListTitle;

    public ?int $nagariId = null;

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()?->hasAnyRole(['superadmin', 'dpmd', 'pengajar']) ?? false) {
            NagariContext::ensureDefault(NagariContext::LMS_REKAP);
            $this->nagariId = NagariContext::id(NagariContext::LMS_REKAP);
        }
    }

    // Pemilih nagari inline (pola sama Warga/Wilayah/UMKM) — ganti nagariId → NagariContext
    // (namespace LMS_REKAP, independen dari menu lain) ikut disetel, tabel langsung
    // ter-render ulang, tanpa reload.
    public function updatedNagariId(): void
    {
        if ((auth()->user()?->hasAnyRole(['superadmin', 'dpmd', 'pengajar']) ?? false) && $this->nagariId !== null) {
            NagariContext::set(NagariContext::LMS_REKAP, $this->nagariId);
        }
    }

    public function content(Schema $schema): Schema
    {
        $components = parent::content($schema)->getComponents();
        array_splice($components, 1, 0, [
            View::make('filament.components.nagari-picker-banner')
                ->viewData(['roles' => ['superadmin', 'dpmd', 'pengajar']]),
        ]);

        return $schema->components($components);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
