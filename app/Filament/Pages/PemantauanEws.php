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
use App\Models\EwsReading;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportExporter;
use App\Support\Reports\TabularReport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;

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

    protected function getHeaderActions(): array
    {
        $schema = fn (): array => [
            Select::make('hari')
                ->label('Periode data')
                ->options([7 => '7 hari terakhir', 30 => '30 hari terakhir', 90 => '90 hari terakhir', 365 => '1 tahun terakhir'])
                ->default(30)
                ->required(),
        ];

        return [
            ActionGroup::make([
                Action::make('ewsExcel')->label('Excel (.xlsx)')->icon('heroicon-o-table-cells')->schema($schema)
                    ->action(fn (array $data) => app(ReportExporter::class)->xlsx($this->ewsReport((int) $data['hari']), auth()->user())),
                Action::make('ewsPdf')->label('PDF (.pdf)')->icon('heroicon-o-document-text')->schema($schema)
                    ->action(fn (array $data) => app(ReportExporter::class)->pdf($this->ewsReport((int) $data['hari']), auth()->user())),
            ])->label('Ekspor')->icon('heroicon-o-arrow-down-tray')->button()->color('gray'),
        ];
    }

    private function ewsReport(int $hari = 30): TabularReport
    {
        $nagari = $this->nagariTerpilih;
        abort_unless($nagari, 404);
        $mulai = now()->subDays(max(1, $hari) - 1)->startOfDay();

        return new TabularReport(
            title: 'Riwayat Pemantauan EWS',
            filename: 'riwayat-ews-'.$nagari->nama,
            query: EwsReading::query()
                ->whereHas('device', fn ($query) => $query->where('nagari_id', $nagari->getKey()))
                ->where('direkam_pada', '>=', $mulai)
                ->with('device')
                ->orderByDesc('direkam_pada'),
            columns: [
                new ReportColumn('direkam_pada', 'Waktu', 20, fn ($value): string => $value?->format('d/m/Y H:i:s') ?? '—'),
                new ReportColumn('device.nama_lokasi', 'Titik Pantau', 28, fn ($value): string => $value ?: 'Titik pantau utama'),
                new ReportColumn('tinggi_air', 'Tinggi Air', 14),
                new ReportColumn('curah_hujan', 'Curah Hujan', 15),
                new ReportColumn('ph_air', 'pH Air', 12),
                new ReportColumn('getaran', 'Getaran', 12),
                new ReportColumn('status_sungai', 'Status Sungai', 18),
                new ReportColumn('terhubung', 'Koneksi', 14, fn ($value): string => $value ? 'Terhubung' : 'Terputus'),
            ],
            metadata: ['Cakupan' => $nagari->nama, 'Periode' => $mulai->format('d/m/Y').' – '.now()->format('d/m/Y')],
        );
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
