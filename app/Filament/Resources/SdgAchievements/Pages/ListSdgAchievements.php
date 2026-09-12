<?php

namespace App\Filament\Resources\SdgAchievements\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Filament\Resources\SdgAchievements\SdgAchievementResource;
use App\Models\Nagari;
use App\Models\RefWilayah;
use App\Models\SdgAchievement;
use App\Models\SdgGoal;
use App\Services\Sdg\SdgRefreshService;
use App\Services\Sdg\SdgScoringService;
use App\Support\Reports\ReportActionGroup;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\TabularReport;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Pemilih Poin SDGs berupa GRID 18 KARTU ber-ikon resmi (6 kartu per baris,
 * bukan tabel/daftar). Klik kartu → form isi capaian poin itu (create bila
 * belum ada, edit bila sudah). Super admin memilih nagari; tanpa dimensi tahun
 * (capaian = potret berjalan).
 */
class ListSdgAchievements extends Page
{
    use HasPanelBreadcrumbs;

    protected static string $resource = SdgAchievementResource::class;

    protected string $view = 'filament.resources.sdg-achievements.pilih-poin';

    public ?int $nagariId = null;

    public function getTitle(): string
    {
        return 'Capaian SDGs';
    }

    /**
     * Tombol "Perbarui dari Kemendesa" — ambil ulang skor untuk nagari terpilih.
     * Mendukung opsi rujukan nagari lain / kode BPS manual untuk nagari hasil pemekaran.
     * Disembunyikan dari DPMD (read-only). Validasi cooldown: bila skor baru saja
     * diambil (SDGs jarang berubah), konfirmasi memperingatkan agar tak menghajar
     * endpoint tak resmi tanpa perlu. Spinner aksi tampil selama proses.
     */
    protected function getHeaderActions(): array
    {
        return [
            ReportActionGroup::make(fn (): TabularReport => $this->sdgReport()),
            Action::make('perbaruiSdgs')
                ->label('Perbarui dari Kemendesa')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->authorize(fn (): bool => ($nagari = $this->getNagariTerpilihProperty()) !== null
                    && (auth()->user()?->can('update', $nagari) ?? false))
                ->visible(fn (): bool => $this->nagariAktif() !== null
                    && ! (auth()->user()?->isDpmd() ?? false)
                    && (app()->environment('local') || str_ends_with(request()->getHost(), '.test') || in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']) || !empty(env('KEMENDESA_PROXY_URL'))))
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'Perbarui skor SDGs: '.($this->getNagariTerpilihProperty()?->nama ?? 'Nagari'))
                ->modalDescription(function (): string {
                    $nagari = $this->getNagariTerpilihProperty();
                    $svc = app(SdgRefreshService::class);
                    $last = $nagari ? $svc->lastFetchedAt($nagari) : null;

                    if ($nagari && $svc->withinCooldown($nagari)) {
                        return "Skor {$nagari->nama} baru diambil {$last->diffForHumans()}. "
                            .'SDGs Desa jarang berubah, jadi biasanya tak perlu diperbarui lagi. '
                            .'Tetap ambil ulang dari Kemendesa?';
                    }

                    return 'Ambil skor SDGs 18 poin untuk '.($nagari?->nama ?? 'nagari ini')
                        .' dari Kemendesa. Anda dapat menggunakan kode nagari ini atau kode nagari rujukan/induk jika hasil pemekaran.';
                })
                ->schema([
                    Radio::make('mode_sumber')
                        ->label('Pilihan Kode Wilayah Kemendesa')
                        ->options([
                            'sendiri' => 'Gunakan kode wilayah nagari ini sendiri',
                            'nagari_lain' => 'Gunakan kode nagari lain / nagari induk (Pemekaran)',
                            'manual' => 'Input manual kode BPS (10 digit)',
                        ])
                        ->default('sendiri')
                        ->live()
                        ->helperText(function () {
                            $nagari = $this->getNagariTerpilihProperty();
                            $kodeBps = $nagari?->wilayah_kode ? RefWilayah::where('kode', $nagari->wilayah_kode)->value('kode_bps') : null;

                            return $kodeBps
                                ? "Kode BPS terdaftar nagari ini: {$kodeBps}."
                                : 'Perhatian: Nagari ini belum memiliki kode BPS resmi di sistem.';
                        }),

                    Select::make('nagari_rujukan_id')
                        ->label('Pilih Nagari Mitra Rujukan / Induk')
                        ->placeholder('Pilih salah satu nagari mitra...')
                        ->options(function () {
                            $nagariAktifId = $this->nagariAktif();

                            return Nagari::query()
                                ->whereNotNull('wilayah_kode')
                                ->when($nagariAktifId, fn ($q) => $q->where('id', '!=', $nagariAktifId))
                                ->orderBy('nama')
                                ->get()
                                ->mapWithKeys(function (Nagari $n) {
                                    $kodeBps = RefWilayah::where('kode', $n->wilayah_kode)->value('kode_bps');

                                    return [$n->id => "{$n->nama} ({$n->kabupaten}) ".($kodeBps ? "— BPS: {$kodeBps}" : '(Tanpa BPS)')];
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->visible(fn ($get): bool => $get('mode_sumber') === 'nagari_lain')
                        ->live()
                        ->helperText('Pilih nagari induk terdaftar, ATAU gunakan pencarian master nagari se-Sumbar di bawah jika belum terdaftar sebagai mitra.'),

                    Select::make('wilayah_rujukan_bps')
                        ->label('Atau Cari Master Nagari/Desa Se-Sumatera Barat (RefWilayah)')
                        ->placeholder('Ketik nama nagari/desa induk...')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return RefWilayah::query()
                                ->where('level', 4)
                                ->whereNotNull('kode_bps')
                                ->where('nama', 'like', "%{$search}%")
                                ->limit(25)
                                ->get()
                                ->mapWithKeys(fn (RefWilayah $w) => [$w->kode_bps => "{$w->nama} (Kode BPS: {$w->kode_bps})"])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn (?string $value): ?string => $value ? (RefWilayah::where('kode_bps', $value)->value('nama')." (Kode BPS: {$value})") : null)
                        ->visible(fn ($get): bool => $get('mode_sumber') === 'nagari_lain')
                        ->helperText('Opsi pencarian jika nagari induk belum terdaftar di aplikasi BASAMO.'),

                    TextInput::make('kode_bps_manual')
                        ->label('Kode BPS Kemendesa (10 Digit)')
                        ->placeholder('Contoh: 1302030001')
                        ->visible(fn ($get): bool => $get('mode_sumber') === 'manual')
                        ->required(fn ($get): bool => $get('mode_sumber') === 'manual')
                        ->length(10)
                        ->helperText('Masukkan 10 digit kode BPS nagari/desa yang terdaftar di sid.kemendesa.go.id.'),
                ])
                ->modalSubmitActionLabel('Ya, perbarui')
                ->action(function (array $data): void {
                    $nagari = $this->getNagariTerpilihProperty();

                    if ($nagari === null) {
                        return;
                    }

                    $customKodeBps = null;
                    $mode = $data['mode_sumber'] ?? 'sendiri';
                    $sumberInfo = $nagari->nama;

                    if ($mode === 'manual' && filled($data['kode_bps_manual'] ?? null)) {
                        $customKodeBps = trim((string) $data['kode_bps_manual']);
                        $sumberInfo = "kode manual BPS {$customKodeBps}";
                    } elseif ($mode === 'nagari_lain') {
                        if (filled($data['wilayah_rujukan_bps'] ?? null)) {
                            $customKodeBps = trim((string) $data['wilayah_rujukan_bps']);
                            $namaRef = RefWilayah::where('kode_bps', $customKodeBps)->value('nama') ?? $customKodeBps;
                            $sumberInfo = "nagari rujukan {$namaRef} (BPS: {$customKodeBps})";
                        } elseif (filled($data['nagari_rujukan_id'] ?? null)) {
                            $nagariRujukan = Nagari::find($data['nagari_rujukan_id']);
                            if ($nagariRujukan?->wilayah_kode) {
                                $customKodeBps = RefWilayah::where('kode', $nagariRujukan->wilayah_kode)->value('kode_bps');
                            }
                            $sumberInfo = "nagari induk {$nagariRujukan?->nama} (BPS: {$customKodeBps})";
                        }

                        if (! $customKodeBps) {
                            Notification::make()
                                ->title('Gagal memperbarui')
                                ->body('Silakan tentukan nagari rujukan atau kode BPS yang valid.')
                                ->warning()
                                ->send();

                            return;
                        }
                    }

                    $hasil = app(SdgRefreshService::class)->refreshNagari($nagari, $customKodeBps);

                    match ($hasil['status']) {
                        'ok' => Notification::make()
                            ->title('Skor SDGs diperbarui')
                            ->body("Capaian 18 poin untuk {$nagari->nama} berhasil ditarik dari Kemendesa menggunakan {$sumberInfo}.")
                            ->success()
                            ->send(),
                        'tanpa_bps' => Notification::make()
                            ->title('Gagal memperbarui')
                            ->body('Kode BPS wilayah belum tersedia untuk nagari ini. Silakan gunakan opsi kode nagari lain atau input manual.')
                            ->warning()
                            ->send(),
                        default => Notification::make()
                            ->title('Gagal memperbarui')
                            ->body('Server Kemendesa tak merespons atau data untuk kode BPS ('.($hasil['kode_bps'] ?? '-').') belum ada di Kemendesa.')
                            ->danger()
                            ->send(),
                    };
                }),
        ];
    }

    private function sdgReport(): TabularReport
    {
        $nagari = $this->getNagariTerpilihProperty();
        abort_unless($nagari, 404);

        return new TabularReport(
            title: 'Capaian SDGs Nagari',
            filename: 'capaian-sdgs-'.$nagari->nama,
            query: SdgAchievement::query()->where('nagari_id', $nagari->getKey())->with(['goal.pillar'])->orderBy('sdg_goal_id'),
            columns: [
                new ReportColumn('goal.nomor', 'Poin', 10),
                new ReportColumn('goal.nama', 'Tujuan SDGs', 42),
                new ReportColumn('goal.pillar.nama', 'Pilar', 24),
                new ReportColumn('persentase', 'Capaian (%)', 15, fn ($value): string => number_format((float) $value, 2, ',', '.')),
                new ReportColumn('fetched_at', 'Terakhir Diperbarui', 22, fn ($value): string => $value?->format('d/m/Y H:i') ?? 'Belum tersedia'),
            ],
            metadata: ['Cakupan' => $nagari->nama],
            orientation: 'portrait',
        );
    }

    public function mount(): void
    {
        // ?nagari= dari aksi "Kelola SDGs" di daftar Nagari (super admin) — pra-pilih
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

    /** Nagari aktif sebagai model — identitas pada banner skor. */
    public function getNagariTerpilihProperty(): ?Nagari
    {
        $nagariId = $this->nagariAktif();

        return $nagariId !== null ? Nagari::query()->find($nagariId) : null;
    }

    /** @return array{skor: float, kelengkapan: array{terisi: int, total: int, persen: float}} */
    public function getRingkasanProperty(): array
    {
        $nagariId = $this->nagariAktif();

        if ($nagariId === null) {
            return ['skor' => 0.0, 'kelengkapan' => ['terisi' => 0, 'total' => 0, 'persen' => 0.0]];
        }

        $svc = app(SdgScoringService::class);

        return [
            'skor' => $svc->skorNagari($nagariId),
            'kelengkapan' => $svc->kelengkapan($nagariId),
        ];
    }

    /**
     * 18 kartu poin: capaian + tautan ke viewer baca-saja (hanya bila sudah ada
     * data — belum ditarik dari API Kemendesa berarti belum ada apa pun dilihat).
     *
     * @return Collection<int, array{goal: SdgGoal, achievement: SdgAchievement|null, nilai: float, terisi: bool, url: string|null}>
     */
    public function getKartuProperty(): Collection
    {
        $nagariId = $this->nagariAktif();

        if ($nagariId === null) {
            return collect();
        }

        return app(SdgScoringService::class)
            ->capaianPoin($nagariId)
            ->map(fn (array $p): array => [
                ...$p,
                'url' => $p['achievement'] !== null
                    ? SdgAchievementResource::getUrl('view', ['record' => $p['achievement']])
                    : null,
            ]);
    }
}
