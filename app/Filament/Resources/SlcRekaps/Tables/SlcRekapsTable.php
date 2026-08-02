<?php

namespace App\Filament\Resources\SlcRekaps\Tables;

use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Models\User;
use App\Services\SlcRekapService;
use App\Support\NagariContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SlcRekapsTable
{
    public static function configure(Table $table): Table
    {
        $nagariId = auth()->user()?->managedNagariId(NagariContext::LMS_REKAP);

        $actor = auth()->user();
        $rekap = app(SlcRekapService::class);
        $modules = $rekap->modulesFor($actor, $nagariId)
            ->with('materis:id,module_id')
            ->get(['id']);
        $totalModul = $modules->count();
        $materiIds = $modules->flatMap(
            fn ($module): array => $module->materis
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
        );
        $totalMateri = $materiIds->count();
        $evaluasis = $rekap->evaluasisFor($actor, $nagariId)->get(['id', 'jenis']);
        $totalPretest = $evaluasis->where('jenis', JenisEvaluasi::Pretest)->count();
        $totalEvaluasi = $evaluasis->where('jenis', JenisEvaluasi::Kegiatan)->count();

        return $table
            // Tabel hanya berisi ringkasan faktual. Seluruh riwayat dipindahkan ke
            // halaman detail penuh agar tidak dipadatkan ke modal atau kartu tabel.
            ->recordUrl(fn (User $record): string => SlcRekapResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('modul_selesai')
                    ->label('Modul')
                    ->getStateUsing(function (User $record) use ($totalModul): string {
                        $selesai = $record->moduleProgress->where('status', ModuleProgressStatus::Completed)->count();
                        $berjalan = $record->moduleProgress->where('status', ModuleProgressStatus::InProgress)->count();

                        return "{$selesai} selesai · {$berjalan} berjalan · {$totalModul} total";
                    })
                    ->badge()
                    ->color(fn (User $record): string => $record->moduleProgress
                        ->where('status', ModuleProgressStatus::Completed)->count() > 0 ? 'success' : 'gray')
                    ->alignCenter(),

                TextColumn::make('materi_selesai')
                    ->label('Materi')
                    ->getStateUsing(fn (User $record): string => $record->moduleProgress
                        ->flatMap(fn ($progress): array => $progress->halaman_selesai ?? [])
                        ->map(fn ($id): int => (int) $id)
                        ->intersect($materiIds)
                        ->unique()
                        ->count()." / {$totalMateri}")
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('pretest')
                    ->label('Pre-test')
                    ->getStateUsing(function (User $record) use ($totalPretest): string {
                        $dikerjakan = $record->evaluasiPercobaans
                            ->filter(fn ($attempt): bool => $attempt->evaluasi?->jenis === JenisEvaluasi::Pretest)
                            ->pluck('evaluasi_id')->unique()->count();

                        return "{$dikerjakan} / {$totalPretest}";
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('evaluasi_kegiatan')
                    ->label('Evaluasi Kegiatan')
                    ->getStateUsing(function (User $record) use ($totalEvaluasi): string {
                        $attempts = $record->evaluasiPercobaans
                            ->filter(fn ($attempt): bool => $attempt->evaluasi?->jenis === JenisEvaluasi::Kegiatan);
                        $dikerjakan = $attempts->pluck('evaluasi_id')->unique()->count();
                        $lulus = $attempts->where('status', StatusPercobaan::Passed)
                            ->pluck('evaluasi_id')->unique()->count();

                        return "{$dikerjakan} / {$totalEvaluasi} · {$lulus} lulus";
                    })
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('diskusi_count')
                    ->label('Forum Diskusi')
                    ->getStateUsing(function (User $record): string {
                        $topik = $record->discussions->whereNull('parent_id')->count();
                        $balasan = $record->discussions->whereNotNull('parent_id')->count();

                        return "{$topik} topik · {$balasan} balasan";
                    })
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('aktivitas_terakhir')
                    ->label('Aktivitas Terakhir')
                    ->getStateUsing(fn (User $record) => collect([
                        $record->moduleProgress->max('updated_at'),
                        $record->evaluasiPercobaans->max(
                            fn ($attempt) => $attempt->submitted_at ?? $attempt->created_at
                        ),
                        $record->discussions->max('created_at'),
                    ])->filter()->sortDesc()->first())
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum ada')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    Action::make('lihat')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->authorize('view')
                        ->url(fn (User $record): string => SlcRekapResource::getUrl('view', ['record' => $record])),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ]);
    }
}
