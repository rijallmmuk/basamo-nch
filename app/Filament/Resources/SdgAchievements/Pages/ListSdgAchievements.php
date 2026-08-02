<?php

namespace App\Filament\Resources\SdgAchievements\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Filament\Resources\SdgAchievements\SdgAchievementResource;
use App\Models\Nagari;
use App\Models\SdgAchievement;
use App\Models\SdgGoal;
use App\Services\Sdg\SdgRefreshService;
use App\Services\Sdg\SdgScoringService;
use Filament\Actions\Action;
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
     * Disembunyikan dari DPMD (read-only). Validasi cooldown: bila skor baru saja
     * diambil (SDGs jarang berubah), konfirmasi memperingatkan agar tak menghajar
     * endpoint tak resmi tanpa perlu. Spinner aksi tampil selama proses.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('perbaruiSdgs')
                ->label('Perbarui dari Kemendesa')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->authorize(fn (): bool => ($nagari = $this->getNagariTerpilihProperty()) !== null
                    && (auth()->user()?->can('update', $nagari) ?? false))
                ->visible(fn (): bool => $this->nagariAktif() !== null
                    && ! (auth()->user()?->isDpmd() ?? false)
                    && in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']))
                ->requiresConfirmation()
                ->modalHeading('Perbarui skor SDGs?')
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
                        .' dari Kemendesa. Prosesnya bisa memakan beberapa detik.';
                })
                ->modalSubmitActionLabel('Ya, perbarui')
                ->action(function (): void {
                    $nagari = $this->getNagariTerpilihProperty();

                    if ($nagari === null) {
                        return;
                    }

                    $hasil = app(SdgRefreshService::class)->refreshNagari($nagari);

                    match ($hasil['status']) {
                        'ok' => Notification::make()
                            ->title('Skor SDGs diperbarui')
                            ->body('Capaian 18 poin '.$nagari->nama.' berhasil ditarik ulang dari Kemendesa.')
                            ->success()
                            ->send(),
                        'tanpa_bps' => Notification::make()
                            ->title('Gagal memperbarui')
                            ->body('Kode BPS wilayah nagari ini belum tersedia.')
                            ->warning()
                            ->send(),
                        default => Notification::make()
                            ->title('Gagal memperbarui')
                            ->body('Server Kemendesa tak merespons. Coba lagi beberapa saat.')
                            ->danger()
                            ->send(),
                    };
                }),
        ];
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
