<?php

namespace App\Support\Dashboard;

use App\Enums\ModuleProgressStatus;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\SdgAchievement;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OperatorDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public static function forNagari(?Nagari $nagari): array
    {
        if (! $nagari) {
            return [
                'nagari' => null,
                'metrics' => self::metrikKosong(),
                'lmsModules' => ['categories' => ['Belum ada modul'], 'completed' => [0], 'inProgress' => [0]],
                'sdgs' => ['categories' => ['SDGs 1-18'], 'values' => [0]],
            ];
        }

        $nagariId = $nagari->id;

        // 1. Kependudukan. Penduduk dan akun warga SENGAJA dipisah: `penduduk` adalah
        // seluruh warga yang terdata, `users` hanya yang punya akun portal. Dulu
        // keduanya berdampingan dalam satu kartu sehingga jumlah laki-laki ditambah
        // perempuan tidak pernah sama dengan angka besarnya.
        $demografi = DemografiData::ringkasan($nagariId);
        $pendudukCount = $demografi['penduduk'];
        $priaCount = $demografi['laki_laki'];
        $wanitaCount = $demografi['perempuan'];

        // Sasaran dipakai sebagai SUBQUERY. Satu nagari dapat berisi puluhan ribu
        // warga, dan menariknya jadi daftar id di PHP membuat dasbor operator
        // menyalin seluruh tabel warga ke memori setiap kali dibuka.
        $wargaQuery = fn (): Builder => User::role('warga')->where('nagari_id', $nagariId)->select('users.id');
        $akunWargaCount = $demografi['akun_portal'];

        // 2. Belajar
        $lmsCompletedCount = UserModuleProgress::whereIn('user_id', $wargaQuery())
            ->where('status', ModuleProgressStatus::Completed->value)
            ->count();

        $lmsActiveCount = UserModuleProgress::whereIn('user_id', $wargaQuery())
            ->distinct('user_id')
            ->count('user_id');

        $avgEvaluasi = EvaluasiPercobaan::whereIn('user_id', $wargaQuery())
            ->whereNotNull('nilai')
            ->avg('nilai');
        $avgEvaluasiScore = $avgEvaluasi !== null ? number_format((float) $avgEvaluasi, 1, ',', '.') : '—';

        // 3. Ekonomi UMKM
        $umkmCount = UmkmProfile::where('nagari_id', $nagariId)->count();
        $produkNagari = fn (): Builder => UmkmProduct::whereHas('umkmProfile', fn ($q) => $q->where('nagari_id', $nagariId));
        $productPublishedCount = $produkNagari()->count();
        // Kunjungan PRODUK, bukan kunjungan etalase — lihat catatan yang sama di
        // SuperadminDashboardData.
        $productTotalViews = (int) $produkNagari()->sum('jumlah_dilihat');
        $etalaseTotalViews = (int) UmkmProfile::where('nagari_id', $nagariId)->sum('jumlah_dilihat');

        // 4. SDGs & IDM
        $sdgAvg = SdgAchievement::where('nagari_id', $nagariId)->avg('persentase');
        $sdgAvgScore = $sdgAvg !== null ? number_format((float) $sdgAvg, 1, ',', '.').'%' : '0%';

        // Nagari yang belum pernah ditarik datanya TIDAK boleh diberi status karangan:
        // "Berkembang" terbaca operator sebagai fakta resmi Kemendesa.
        $statusIdm = $nagari->latestIdmStatus?->status ?? 'Belum ada data';

        return [
            'nagari' => $nagari,
            'metrics' => [
                'pendudukCount' => $pendudukCount,
                'akunWargaCount' => $akunWargaCount,
                'priaCount' => $priaCount,
                'wanitaCount' => $wanitaCount,
                'lmsCompletedCount' => $lmsCompletedCount,
                'lmsActiveCount' => $lmsActiveCount,
                'avgEvaluasiScore' => $avgEvaluasiScore,
                'umkmCount' => $umkmCount,
                'productPublishedCount' => $productPublishedCount,
                'productTotalViews' => $productTotalViews,
                'etalaseTotalViews' => $etalaseTotalViews,
                'sdgAvgScore' => $sdgAvgScore,
                'statusIdm' => $statusIdm,
            ],
            'lmsModules' => self::progresModul($nagariId, $wargaQuery()),
            'sdgs' => self::sdgs($nagariId),
        ];
    }

    /** @return array<string, mixed> */
    private static function metrikKosong(): array
    {
        return [
            'pendudukCount' => 0,
            'akunWargaCount' => 0,
            'priaCount' => 0,
            'wanitaCount' => 0,
            'lmsCompletedCount' => 0,
            'lmsActiveCount' => 0,
            'avgEvaluasiScore' => '—',
            'umkmCount' => 0,
            'productPublishedCount' => 0,
            'productTotalViews' => 0,
            'etalaseTotalViews' => 0,
            'sdgAvgScore' => '0%',
            'statusIdm' => 'Belum ada data',
        ];
    }

    /**
     * Progres enam modul teratas, dihitung dengan satu query beragregat.
     *
     * @return array<string, mixed>
     */
    private static function progresModul(int $nagariId, Builder $wargaQuery): array
    {
        $modules = Module::query()
            ->where(fn ($q) => $q->whereNull('pelatihan_id')->orWhereHas('pelatihan', fn ($prg) => $prg->forNagari($nagariId)))
            ->take(6)
            ->get(['id', 'judul']);

        if ($modules->isEmpty()) {
            return ['categories' => ['Belum ada modul'], 'completed' => [0], 'inProgress' => [0]];
        }

        $counts = UserModuleProgress::query()
            ->whereIn('module_id', $modules->modelKeys())
            ->whereIn('user_id', $wargaQuery)
            ->selectRaw('module_id, status, COUNT(*) as jumlah')
            ->groupBy('module_id', 'status')
            ->get()
            ->groupBy('module_id');

        return [
            'categories' => $modules->map(fn (Module $m): string => Str::limit($m->judul, 20))->all(),
            'completed' => $modules->map(fn (Module $m): int => self::ambil($counts, $m->id, ModuleProgressStatus::Completed))->all(),
            'inProgress' => $modules->map(fn (Module $m): int => self::ambil($counts, $m->id, ModuleProgressStatus::InProgress))->all(),
        ];
    }

    /**
     * @param  Collection<int, Collection<int, UserModuleProgress>>  $counts
     */
    private static function ambil($counts, int $moduleId, ModuleProgressStatus $status): int
    {
        // Status bisa datang sebagai enum (karena cast model) atau sebagai string
        // mentah; keduanya dicocokkan lewat nilainya supaya tidak diam-diam nol.
        $baris = $counts->get($moduleId)?->first(fn ($row): bool => (
            $row->status instanceof ModuleProgressStatus ? $row->status->value : $row->status
        ) === $status->value);

        return (int) ($baris->jumlah ?? 0);
    }

    /** @return array<string, mixed> */
    private static function sdgs(int $nagariId): array
    {
        $rows = SdgAchievement::where('nagari_id', $nagariId)
            ->with('goal')
            ->orderBy('sdg_goal_id')
            ->get();

        if ($rows->isEmpty()) {
            return ['categories' => ['SDGs 1-18'], 'values' => [0]];
        }

        return [
            'categories' => $rows->map(function (SdgAchievement $row): string {
                $nomor = $row->goal?->nomor ?? $row->sdg_goal_id;

                return "Poin {$nomor}: ".($row->goal?->nama ?? "Poin {$nomor}");
            })->all(),
            'values' => $rows->map(fn (SdgAchievement $row): float => (float) $row->persentase)->all(),
        ];
    }
}
