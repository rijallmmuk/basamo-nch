<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Models\Nagari;
use App\Support\Dashboard\PengajarNagariData;
use App\Support\NagariContext;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

/**
 * "Nagari mana yang tertinggal" — pertanyaan yang tidak terjawab oleh angka per
 * modul, karena satu modul dapat menyasar banyak nagari sekaligus.
 */
class PengajarNagariTableWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $rekap = null;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Sebaran Belajar per Nagari';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('pengajar');
    }

    public function table(Table $table): Table
    {
        $rekap = $this->rekap();

        return $table
            ->description('Dihitung dari modul berisi materi yang pelatihannya menyasar nagari tersebut.')
            ->query(
                Nagari::query()
                    ->whereKey($rekap->keys()->all())
                    ->orderBy('nama')
            )
            ->emptyStateHeading('Belum ada nagari sasaran')
            ->emptyStateDescription('Sasaran nagari diatur pada pelatihan, dan modulnya harus sudah berisi materi.')
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nagari')
                    ->description(fn (Nagari $record): ?string => $record->kabupaten)
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('modul')
                    ->label('Modul Ditujukan')
                    ->getStateUsing(fn (Nagari $record): int => $this->baris($record)['modul'])
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('mulai')
                    ->label('Warga Mulai Belajar')
                    ->getStateUsing(function (Nagari $record): string {
                        $baris = $this->baris($record);
                        $persen = $baris['warga'] > 0
                            ? round($baris['mulai'] / $baris['warga'] * 100)
                            : 0;

                        return "{$baris['mulai']} / {$baris['warga']} ({$persen}%)";
                    })
                    ->badge()
                    ->color(fn (Nagari $record): string => $this->baris($record)['mulai'] > 0 ? 'success' : 'gray')
                    ->alignCenter(),

                TextColumn::make('tuntas')
                    ->label('Penuntasan Modul')
                    ->getStateUsing(function (Nagari $record): string {
                        $baris = $this->baris($record);
                        $persen = PengajarNagariData::persenTuntas($baris);

                        return $persen === null
                            ? '—'
                            : "{$persen}% ({$baris['selesai']} selesai)";
                    })
                    // Warna sengaja bertingkat: pengajar mencari nagari yang tertinggal,
                    // bukan sekadar membaca angkanya satu per satu.
                    ->badge()
                    ->color(function (Nagari $record): string {
                        $persen = PengajarNagariData::persenTuntas($this->baris($record));

                        return match (true) {
                            $persen === null => 'gray',
                            $persen >= 60 => 'success',
                            $persen >= 25 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->alignCenter(),

                TextColumn::make('nilai')
                    ->label('Evaluasi Kegiatan')
                    ->getStateUsing(function (Nagari $record): string {
                        $baris = $this->baris($record);

                        if ($baris['pengerjaan'] === 0) {
                            return 'Belum dikerjakan';
                        }

                        $rata = number_format((float) $baris['rataNilai'], 1, ',', '.');

                        return "{$rata} · {$baris['lulus']} lulus dari {$baris['pengerjaan']}";
                    })
                    ->badge()
                    ->color(fn (Nagari $record): string => $this->baris($record)['pengerjaan'] > 0 ? 'warning' : 'gray')
                    ->alignCenter(),

                TextColumn::make('diskusi')
                    ->label('Diskusi')
                    ->getStateUsing(function (Nagari $record): string {
                        $baris = $this->baris($record);

                        return "{$baris['topik']} topik · {$baris['balasan']} balasan";
                    })
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->recordActions([
                Action::make('rekap')
                    ->label('Lihat Warga')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    // Rekap SLC memilih nagari lewat sesi, bukan parameter URL, jadi
                    // konteksnya disetel dulu supaya pengajar mendarat di nagari yang
                    // memang barusan diklik.
                    ->action(function (Nagari $record) {
                        NagariContext::set(NagariContext::LMS_REKAP, $record->getKey());

                        return redirect(SlcRekapResource::getUrl('index'));
                    }),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    /** @return array<string, mixed> */
    private function baris(Nagari $nagari): array
    {
        return $this->rekap()->get($nagari->getKey()) ?? [
            'warga' => 0, 'modul' => 0, 'mulai' => 0, 'selesai' => 0,
            'rataNilai' => null, 'pengerjaan' => 0, 'lulus' => 0, 'topik' => 0, 'balasan' => 0,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rekap(): Collection
    {
        if ($this->rekap !== null) {
            return $this->rekap;
        }

        $user = auth()->user();

        return $this->rekap = $user ? PengajarNagariData::rekap($user) : collect();
    }
}
