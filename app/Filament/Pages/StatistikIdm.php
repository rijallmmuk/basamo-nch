<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Models\IdmIndicator;
use App\Models\IdmStatus;
use App\Models\Nagari;
use App\Services\Idm\IdmRefreshService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use App\Support\Reports\ReportActionGroup;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\TabularReport;

/**
 * Statistik IDM (Indeks Desa Membangun) per nagari: skor total + status, 3 sub-indeks
 * dimensi (IKS/IKE/IKL) sebagai diagram, dan seluruh ~50 indikator berkelompok dengan
 * rekomendasi + sumber dana — keterbukaan informasi. Operator ter-scope nagarinya;
 * superadmin/dpmd memilih nagari (pola sama halaman Capaian SDGs).
 *
 * @property-read Nagari|null $nagariTerpilih
 * @property-read IdmStatus|null $idm
 */
class StatistikIdm extends Page
{
    use HasPanelBreadcrumbs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Statistik IDM';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.statistik-idm';

    public ?int $nagariId = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Status Desa';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator', 'dpmd']);
    }

    public function getTitle(): string
    {
        return 'Statistik IDM';
    }

    public function mount(): void
    {
        $nagariDariUrl = request()->integer('nagari') ?: null;

        $this->nagariId = auth()->user()?->managedNagariId()
            ?? $nagariDariUrl
            ?? Nagari::query()->orderBy('nama')->value('id');
    }

    /** Nagari efektif — operator TIDAK bisa mengintip nagari lain via manipulasi state. */
    protected function nagariAktif(): ?int
    {
        $managed = auth()->user()?->managedNagariId();

        return $managed ?? ($this->nagariId ? (int) $this->nagariId : null);
    }

    /** Pilihan nagari — hanya super admin/dpmd (operator terkunci). */
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

    /** Status IDM tahun terbaru untuk nagari terpilih, beserta seluruh indikatornya. */
    public function getIdmProperty(): ?IdmStatus
    {
        $id = $this->nagariAktif();

        if ($id === null) {
            return null;
        }

        return IdmStatus::with('indicators')
            ->where('nagari_id', $id)
            ->orderByDesc('tahun')
            ->first();
    }

    /**
     * Indikator terlemah (skor ≤ 2) sebagai prioritas perbaikan, DIURUT dari potensi
     * kenaikan indeks (+NILAI) terbesar — inti "statistik yang bisa ditindaklanjuti":
     * perbaiki dulu yang paling menaikkan skor IDM.
     *
     * @return Collection<int, IdmIndicator>
     */
    public function getPrioritasProperty(): Collection
    {
        return $this->idm?->indicators
            ->filter(fn ($i): bool => $i->skor <= 2)
            ->sortByDesc(fn ($i): float => (float) $i->nilai)
            ->values() ?? collect();
    }

    protected function getHeaderActions(): array
    {
        return [
            ReportActionGroup::make(fn (): TabularReport => $this->idmReport()),
            Action::make('perbaruiIdm')
                ->label('Perbarui dari Kemendesa')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->authorize(fn (): bool => $this->nagariTerpilih !== null
                    && (auth()->user()?->can('update', $this->nagariTerpilih) ?? false))
                ->visible(fn (): bool => $this->nagariTerpilih !== null
                    && ! (auth()->user()?->isDpmd() ?? false)
                    && (app()->environment('local') || str_ends_with(request()->getHost(), '.test') || in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'])))
                ->schema([
                    Select::make('tahun')
                        ->label('Tahun data')
                        ->placeholder('Otomatis (cari terbaru)')
                        ->options(fn (): array => array_combine(
                            $r = range((int) now()->year, (int) now()->year - 4),
                            $r,
                        ))
                        ->helperText('Kosongkan untuk mencari tahun terbaru yang tersedia.'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Perbarui status IDM?')
                ->modalDescription(function (): string {
                    $nagari = $this->nagariTerpilih;
                    $svc = app(IdmRefreshService::class);

                    if ($nagari && $svc->withinCooldown($nagari)) {
                        return 'Data IDM nagari ini baru diambil '.$svc->lastFetchedAt($nagari)?->diffForHumans()
                            .'. IDM berubah setahun sekali, jadi biasanya tak perlu diperbarui lagi. Tetap ambil ulang?';
                    }

                    return 'Ambil status & seluruh indikator IDM untuk '.($nagari?->nama ?? 'nagari ini').' dari Kemendesa.';
                })
                ->modalSubmitActionLabel('Ya, perbarui')
                ->action(function (array $data): void {
                    $nagari = $this->nagariTerpilih;

                    if ($nagari === null) {
                        return;
                    }

                    $tahun = filled($data['tahun'] ?? null) ? (int) $data['tahun'] : null;
                    $hasil = app(IdmRefreshService::class)->refreshNagari($nagari, $tahun);

                    match ($hasil['status']) {
                        'ok' => Notification::make()
                            ->title('Status IDM diperbarui')
                            ->body('Data IDM tahun '.$hasil['tahun'].' berhasil ditarik untuk '.$nagari->nama.'.')
                            ->success()
                            ->send(),
                        'tanpa_kode' => Notification::make()
                            ->title('Gagal memperbarui')
                            ->body('Nagari ini belum punya kode wilayah.')
                            ->warning()
                            ->send(),
                        default => Notification::make()
                            ->title('Gagal memperbarui')
                            ->body($tahun !== null
                                ? "Data IDM tahun {$tahun} tidak tersedia di Kemendesa."
                                : 'Server Kemendesa tak merespons atau belum ada data. Coba lagi nanti.')
                            ->danger()
                            ->send(),
                    };
                }),
        ];
    }

    private function idmReport(): TabularReport
    {
        $nagari = $this->nagariTerpilih;
        $idm = $this->idm;
        abort_unless($nagari && $idm, 404, 'Data IDM belum tersedia.');

        return new TabularReport(
            title: 'Statistik Indeks Desa Membangun (IDM)',
            filename: 'statistik-idm-'.$nagari->nama.'-'.$idm->tahun,
            query: IdmIndicator::query()->where('idm_status_id', $idm->getKey())->orderBy('dimensi')->orderBy('nomor'),
            columns: [
                new ReportColumn('dimensi', 'Dimensi', 18, fn ($value): string => $value?->value ?? '—'),
                new ReportColumn('nomor', 'No.', 8),
                new ReportColumn('indikator', 'Indikator', 42),
                new ReportColumn('skor', 'Skor', 10),
                new ReportColumn('keterangan', 'Kondisi', 40),
                new ReportColumn('kegiatan', 'Rekomendasi Kegiatan', 48),
                new ReportColumn('nilai', 'Potensi Kenaikan', 16),
                new ReportColumn('pelaksana', 'Pelaksana/Sumber', 35, fn ($value): string => collect($value ?? [])->map(fn ($item, $key): string => is_array($item) ? implode(': ', $item) : "{$key}: {$item}")->join('; ')),
            ],
            metadata: [
                'Cakupan' => $nagari->nama,
                'Tahun' => (string) $idm->tahun,
                'Skor IDM' => number_format((float) $idm->skor, 4, ',', '.'),
                'Status' => (string) $idm->status,
            ],
        );
    }
}
