<?php

namespace App\Filament\Widgets;

use App\Enums\ModuleProgressStatus;
use App\Filament\Resources\Modules\ModuleResource;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PengajarModuleTableWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    /** @var array<int, array{target: int, learning: int, completed: int}> */
    private array $progressMetrics = [];

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Kinerja Modul yang Saya Kelola';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('pengajar');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                Module::query()
                    ->when($user, fn ($modules) => $modules->manageableBy($user))
                    ->unless($user, fn ($modules) => $modules->whereKey([]))
                    ->with(['pelatihan.tema', 'pelatihan.nagaris', 'evaluasiKegiatan'])
                    ->withCount([
                        'materis as total_materi',
                        'discussions as total_topik' => fn ($discussions) => $discussions
                            ->whereNull('parent_id'),
                        'discussions as topik_belum_dibaca' => fn ($discussions) => $discussions
                            ->whereNull('parent_id')
                            ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user?->id ?? 0)),
                    ])
                    ->orderBy('urutan')
                    ->orderBy('id')
            )
            ->recordUrl(fn (Module $record): string => ModuleResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('judul')
                    ->label('Judul Modul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('pelatihan_nama')
                    ->label('Pelatihan')
                    ->getStateUsing(fn (Module $record): string => $record->pelatihan?->namaTampil() ?? 'Tanpa Pelatihan')
                    ->badge()
                    ->color('info')
                    ->placeholder('Tanpa Pelatihan'),

                TextColumn::make('total_materi')
                    ->label('Isi Modul')
                    ->formatStateUsing(fn ($state): string => ((int) $state).' materi')
                    ->badge()
                    ->color(fn ($state): string => ((int) $state) > 0 ? 'success' : 'warning')
                    ->alignCenter(),

                TextColumn::make('warga_belajar')
                    ->label('Warga Belajar')
                    ->getStateUsing(function (Module $record): string {
                        $metrics = $this->metrics($record);

                        return "{$metrics['learning']} / {$metrics['target']}";
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('completed_rate')
                    ->label('Penyelesaian')
                    ->getStateUsing(function (Module $record): string {
                        $metrics = $this->metrics($record);
                        $sasaran = $metrics['target'];
                        if ($sasaran === 0) {
                            return '0%';
                        }
                        $completed = $metrics['completed'];
                        $rate = round(($completed / $sasaran) * 100);

                        return "{$rate}% ({$completed}/{$sasaran})";
                    })
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('avg_quiz')
                    ->label('Evaluasi Kegiatan')
                    ->getStateUsing(function (Module $record): string {
                        $evaluasiId = $record->evaluasiKegiatan?->id;
                        if (! $evaluasiId) {
                            return 'Belum ada';
                        }
                        $attempts = EvaluasiPercobaan::where('evaluasi_id', $evaluasiId);
                        $count = (clone $attempts)->count();
                        $avg = (clone $attempts)->whereNotNull('nilai')->avg('nilai');

                        return $avg !== null
                            ? number_format((float) $avg, 1, ',', '.')." · {$count} pengerjaan"
                            : 'Belum dikerjakan';
                    })
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                TextColumn::make('forum')
                    ->label('Forum')
                    ->getStateUsing(fn (Module $record): string => "{$record->topik_belum_dibaca} belum dibaca · {$record->total_topik} topik")
                    ->badge()
                    ->color(fn (Module $record): string => $record->topik_belum_dibaca > 0 ? 'warning' : 'gray')
                    ->alignCenter(),

                TextColumn::make('pelatihan.status')
                    ->label('Akses Warga')
                    ->badge()
                    ->alignCenter(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }

    /** @return array{target: int, learning: int, completed: int} */
    private function metrics(Module $module): array
    {
        if (isset($this->progressMetrics[$module->id])) {
            return $this->progressMetrics[$module->id];
        }

        $sasaran = $module->wargaSasaran();

        // Sasaran dipakai sebagai SUBQUERY, bukan ditarik dulu jadi daftar id di PHP.
        // Modul yang menyasar seluruh nagari punya sasaran sebesar tabel warga, dan
        // menariknya sekali per baris tabel membuat dasbor tumbang begitu warganya
        // mencapai puluhan ribu.
        $counts = $module->progress()
            ->whereIn('user_id', (clone $sasaran)->select('users.id'))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $completed = (int) $counts->get(ModuleProgressStatus::Completed->value, 0);
        $inProgress = (int) $counts->get(ModuleProgressStatus::InProgress->value, 0);

        return $this->progressMetrics[$module->id] = [
            'target' => (clone $sasaran)->count(),
            'learning' => $completed + $inProgress,
            'completed' => $completed,
        ];
    }
}
