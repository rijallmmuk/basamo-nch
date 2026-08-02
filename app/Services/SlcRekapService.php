<?php

namespace App\Services;

use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Models\Discussion;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SlcRekapService
{
    /** @return Builder<Module> */
    public function modulesFor(User $actor, ?int $nagariId): Builder
    {
        return Module::query()
            ->ready()
            ->dariPelatihanAktif()
            ->when($nagariId !== null, fn (Builder $modules) => $modules->forNagari($nagariId))
            ->when($actor->isPengajar(), fn (Builder $modules) => $modules->manageableBy($actor));
    }

    /** @return Builder<Evaluasi> */
    public function evaluasisFor(User $actor, ?int $nagariId): Builder
    {
        return Evaluasi::query()
            ->ready()
            ->whereIn('module_id', $this->modulesFor($actor, $nagariId)->select('modules.id'));
    }

    /** @return Builder<Pelatihan> */
    public function programsFor(User $actor, ?int $nagariId): Builder
    {
        return Pelatihan::query()
            ->when($nagariId !== null, fn (Builder $programs) => $programs
                ->where(fn (Builder $audiences) => $audiences
                    ->where('semua_nagari', true)
                    ->orWhereHas('nagaris', fn (Builder $nagaris) => $nagaris
                        ->whereKey($nagariId))))
            ->when($actor->isPengajar(), fn (Builder $programs) => $programs->manageableBy($actor));
    }

    /** @return Builder<Discussion> */
    public function discussionsFor(User $actor, ?int $nagariId): Builder
    {
        return Discussion::query()
            ->whereIn('module_id', $this->modulesFor($actor, $nagariId)->select('modules.id'));
    }

    /**
     * Seluruh data belajar satu warga dalam cakupan aktor dan nagari saat ini.
     *
     * Modul yang belum memiliki baris progres tetap disertakan sebagai "Belum
     * Dimulai", karena daftar modul tersedia adalah sumber denominator yang benar.
     *
     * @return array<string, mixed>
     */
    public function detailFor(User $warga, User $actor, ?int $nagariId): array
    {
        $modules = $this->modulesFor($actor, $nagariId)
            ->with([
                'pelatihan.tema',
                'pelatihan.nagaris',
                'materis:id,module_id,judul,urutan',
            ])
            ->orderBy('pelatihan_id')
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
        $moduleIds = $modules->modelKeys();

        $progress = UserModuleProgress::query()
            ->whereBelongsTo($warga)
            ->whereIn('module_id', $moduleIds)
            ->get()
            ->keyBy('module_id');

        $evaluasis = Evaluasi::query()
            ->ready()
            ->whereIn('module_id', $moduleIds)
            ->get()
            ->keyBy('id');

        // Riwayat percobaan tetap dipertahankan bila evaluasinya diarsipkan.
        // Denominator/capaian di bawah tetap memakai $evaluasis aktif dan siap.
        $historicalEvaluasiIds = Evaluasi::query()
            ->withTrashed()
            ->whereIn('module_id', $moduleIds)
            ->pluck('id');
        $attempts = EvaluasiPercobaan::query()
            ->whereBelongsTo($warga)
            ->whereIn('evaluasi_id', $historicalEvaluasiIds)
            ->with([
                'evaluasi' => fn ($evaluasi) => $evaluasi->withTrashed(),
                'evaluasi.module',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        $discussions = Discussion::query()
            ->whereBelongsTo($warga)
            ->whereIn('module_id', $moduleIds)
            ->with('module.pelatihan.tema')
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        $rows = $modules->map(function (Module $module) use ($progress, $evaluasis, $attempts, $discussions): array {
            $moduleProgress = $progress->get($module->id);
            $materiIds = $module->materis->modelKeys();
            $materiSelesai = collect($moduleProgress?->halaman_selesai ?? [])
                ->map(fn ($id): int => (int) $id)
                ->intersect($materiIds)
                ->unique()
                ->count();
            $moduleEvaluasis = $evaluasis->where('module_id', $module->id);
            $pretest = $moduleEvaluasis->firstWhere('jenis', JenisEvaluasi::Pretest);
            $evaluasi = $moduleEvaluasis->firstWhere('jenis', JenisEvaluasi::Kegiatan);
            $pretestAttempts = $pretest ? $attempts->where('evaluasi_id', $pretest->id) : collect();
            $evaluasiAttempts = $evaluasi ? $attempts->where('evaluasi_id', $evaluasi->id) : collect();
            $moduleDiscussions = $discussions->where('module_id', $module->id);

            return [
                'module' => $module,
                'status' => $moduleProgress?->status ?? ModuleProgressStatus::NotStarted,
                'materi_selesai' => $materiSelesai,
                'materi_total' => $module->materis->count(),
                'started_at' => $moduleProgress?->created_at,
                'last_progress_at' => $moduleProgress?->updated_at,
                'completed_at' => $moduleProgress?->completed_at,
                'pretest' => $pretest,
                'pretest_attempt' => $pretestAttempts->first(),
                'evaluasi' => $evaluasi,
                'evaluasi_attempts' => $evaluasiAttempts,
                'diskusi_topik' => $moduleDiscussions->whereNull('parent_id')->count(),
                'diskusi_balasan' => $moduleDiscussions->whereNotNull('parent_id')->count(),
            ];
        });

        $completedMaterialIds = $progress
            ->flatMap(fn (UserModuleProgress $item): array => $item->halaman_selesai ?? [])
            ->map(fn ($id): int => (int) $id)
            ->intersect($modules->flatMap(fn (Module $module): array => $module->materis->modelKeys()))
            ->unique();
        $pretests = $evaluasis->where('jenis', JenisEvaluasi::Pretest);
        $kegiatans = $evaluasis->where('jenis', JenisEvaluasi::Kegiatan);
        $pretestAttempts = $attempts->whereIn('evaluasi_id', $pretests->keys());
        $kegiatanAttempts = $attempts->whereIn('evaluasi_id', $kegiatans->keys());

        return [
            'warga' => $warga->loadMissing('nagari'),
            'modules' => $rows,
            'attempts' => $attempts,
            'discussions' => $discussions,
            'summary' => [
                'pelatihan_total' => $modules->pluck('pelatihan_id')->unique()->count(),
                'modul_total' => $modules->count(),
                'modul_selesai' => $rows->where('status', ModuleProgressStatus::Completed)->count(),
                'modul_berjalan' => $rows->where('status', ModuleProgressStatus::InProgress)->count(),
                'materi_total' => $modules->sum(fn (Module $module): int => $module->materis->count()),
                'materi_selesai' => $completedMaterialIds->count(),
                'pretest_total' => $pretests->count(),
                'pretest_dikerjakan' => $pretestAttempts->pluck('evaluasi_id')->unique()->count(),
                'evaluasi_total' => $kegiatans->count(),
                'evaluasi_dikerjakan' => $kegiatanAttempts->pluck('evaluasi_id')->unique()->count(),
                'evaluasi_lulus' => $kegiatanAttempts
                    ->where('status', StatusPercobaan::Passed)
                    ->pluck('evaluasi_id')
                    ->unique()
                    ->count(),
                'diskusi_topik' => $discussions->whereNull('parent_id')->count(),
                'diskusi_balasan' => $discussions->whereNotNull('parent_id')->count(),
                'aktivitas_terakhir' => $this->latestActivity($progress, $attempts, $discussions),
            ],
        ];
    }

    private function latestActivity(Collection $progress, Collection $attempts, Collection $discussions): mixed
    {
        return collect([
            $progress->max('updated_at'),
            $attempts->max(fn (EvaluasiPercobaan $attempt) => $attempt->submitted_at ?? $attempt->created_at),
            $discussions->max('created_at'),
        ])->filter()->sortDesc()->first();
    }
}
