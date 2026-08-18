<?php

namespace App\Filament\Resources\SlcRekaps\Pages;

use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\SlcRekaps\SlcRekapResource;
use App\Models\User;
use App\Services\SlcRekapService;
use App\Support\NagariContext;
use App\Support\Reports\ReportColumn;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListSlcRekaps extends ListRecords
{
    protected static string $resource = SlcRekapResource::class;

    use ExportsTableReports;
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
        return [$this->reportActionGroup()];
    }

    protected function reportTitle(): string
    {
        return 'Rekap Belajar Warga';
    }

    protected function reportMetadata(): array
    {
        $nagari = $this->nagariId ? \App\Models\Nagari::find($this->nagariId) : auth()->user()?->nagari;

        return ['Cakupan' => $nagari?->nama ?? 'Lintas nagari sesuai hak akses'];
    }

    protected function reportFilename(): string
    {
        return 'rekap-belajar-warga-'.($this->reportMetadata()['Cakupan'] ?? '');
    }

    protected function reportColumns(): array
    {
        $actor = auth()->user();
        $nagariId = $actor?->managedNagariId(NagariContext::LMS_REKAP);
        $rekap = app(SlcRekapService::class);
        $modules = $rekap->modulesFor($actor, $nagariId)->with('materis:id,module_id')->get(['id']);
        $materiIds = $modules->flatMap(fn ($module) => $module->materis->pluck('id'))->map(fn ($id): int => (int) $id);
        $evaluasis = $rekap->evaluasisFor($actor, $nagariId)->get(['id', 'jenis']);
        $pretestIds = $evaluasis->where('jenis', JenisEvaluasi::Pretest)->pluck('id');
        $kegiatanIds = $evaluasis->where('jenis', JenisEvaluasi::Kegiatan)->pluck('id');

        return [
            new ReportColumn('name', 'Nama Warga', 28),
            new ReportColumn('nagari.nama', 'Nagari', 24),
            new ReportColumn('moduleProgress', 'Modul Selesai', 15, fn ($value, User $record): int => $record->moduleProgress->where('status', ModuleProgressStatus::Completed)->count()),
            new ReportColumn('moduleProgress', 'Modul Berjalan', 15, fn ($value, User $record): int => $record->moduleProgress->where('status', ModuleProgressStatus::InProgress)->count()),
            new ReportColumn('id', 'Total Modul', 12, fn (): int => $modules->count()),
            new ReportColumn('moduleProgress', 'Materi Selesai', 15, fn ($value, User $record): int => $record->moduleProgress
                ->flatMap(fn ($progress): array => $progress->halaman_selesai ?? [])
                ->map(fn ($id): int => (int) $id)->intersect($materiIds)->unique()->count()),
            new ReportColumn('id', 'Total Materi', 12, fn (): int => $materiIds->count()),
            new ReportColumn('evaluasiPercobaans', 'Pre-test Dikerjakan', 18, fn ($value, User $record): int => $record->evaluasiPercobaans
                ->whereIn('evaluasi_id', $pretestIds)->pluck('evaluasi_id')->unique()->count()),
            new ReportColumn('id', 'Total Pre-test', 13, fn (): int => $pretestIds->count()),
            new ReportColumn('evaluasiPercobaans', 'Evaluasi Dikerjakan', 19, fn ($value, User $record): int => $record->evaluasiPercobaans
                ->whereIn('evaluasi_id', $kegiatanIds)->pluck('evaluasi_id')->unique()->count()),
            new ReportColumn('evaluasiPercobaans', 'Evaluasi Lulus', 15, fn ($value, User $record): int => $record->evaluasiPercobaans
                ->whereIn('evaluasi_id', $kegiatanIds)->where('status', StatusPercobaan::Passed)->pluck('evaluasi_id')->unique()->count()),
            new ReportColumn('id', 'Total Evaluasi', 14, fn (): int => $kegiatanIds->count()),
            new ReportColumn('discussions', 'Topik Diskusi', 13, fn ($value, User $record): int => $record->discussions->whereNull('parent_id')->count()),
            new ReportColumn('discussions', 'Balasan Diskusi', 14, fn ($value, User $record): int => $record->discussions->whereNotNull('parent_id')->count()),
            new ReportColumn('id', 'Aktivitas Terakhir', 20, function ($value, User $record): string {
                $latest = collect([
                    $record->moduleProgress->max('updated_at'),
                    $record->evaluasiPercobaans->max(fn ($attempt) => $attempt->submitted_at ?? $attempt->created_at),
                    $record->discussions->max('created_at'),
                ])->filter()->sortDesc()->first();

                return $latest?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Belum ada';
            }),
        ];
    }
}
