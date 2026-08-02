<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Models\Nagari;
use App\Support\NagariContext;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    /** Nagari yang sedang dilihat superadmin/DPMD; operator tidak memakainya. */
    public ?int $nagariId = null;

    /** Menu & judul tab: "Dashboard" (bukan "Dasbor" bawaan Filament). */
    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard';
    }

    /**
     * Sembunyikan judul di atas kartu sapaan — sapaan sudah menjadi pembuka halaman.
     */
    public function getHeading(): string|Htmlable|null
    {
        return '';
    }

    public function mount(): void
    {
        if ($this->memakaiPemilih()) {
            NagariContext::ensureDefault(NagariContext::DASHBOARD);
            $this->nagariId = NagariContext::id(NagariContext::DASHBOARD);
        }
    }

    /**
     * Widget adalah komponen Livewire TERPISAH dari halaman ini, jadi mengubah
     * properti halaman saja tidak membuatnya menggambar ulang. Perubahan nagari
     * disiarkan sebagai event yang didengarkan tiap widget ber-cakupan nagari.
     */
    public function updatedNagariId(): void
    {
        if (! $this->memakaiPemilih() || $this->nagariId === null) {
            return;
        }

        NagariContext::set(NagariContext::DASHBOARD, $this->nagariId);

        $this->dispatch(NagariContext::DASHBOARD_EVENT);
    }

    /**
     * Dasbor superadmin dan DPMD dibelah dua: angka LINTAS nagari di atas, angka
     * SATU nagari di bawah, dipisahkan pembatas yang memuat pemilih nagarinya.
     *
     * Pemilih sengaja tidak di puncak halaman: di sana ia tampak mengatur seluruh
     * isi dasbor, padahal rekap antar nagari di bagian atas tidak terpengaruh
     * sama sekali.
     */
    public function content(Schema $schema): Schema
    {
        $pilihan = $this->memakaiPemilih()
            ? Nagari::query()->orderBy('nama')->get()
            : collect();

        if ($pilihan->isEmpty()) {
            return parent::content($schema);
        }

        [$perNagari, $lintasNagari] = collect($this->getWidgets())
            ->partition(fn (string|WidgetConfiguration $widget): bool => in_array(
                ScopedToNagari::class,
                class_uses_recursive($this->normalizeWidgetClass($widget)),
                true,
            ));

        return $schema->components([
            Grid::make($this->getColumns())
                ->schema($this->getWidgetsSchemaComponents($lintasNagari->all())),

            View::make('filament.components.dashboard-nagari-section')
                ->viewData(['pilihan' => $pilihan]),

            Grid::make($this->getColumns())
                ->schema($this->getWidgetsSchemaComponents($perNagari->all())),
        ]);
    }

    /**
     * Operator sudah terkunci ke nagarinya sendiri sehingga tak perlu memilih;
     * pengajar dan pemilik UMKM tidak punya widget ber-cakupan nagari di dasbornya.
     */
    private function memakaiPemilih(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'dpmd']);
    }
}
