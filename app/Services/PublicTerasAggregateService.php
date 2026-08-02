<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Models\IdmStatus;
use App\Models\Nagari;
use App\Models\Penduduk;
use App\Models\SdgAchievement;
use App\Models\UmkmProduct;
use App\Models\UserModuleProgress;
use Illuminate\Database\Eloquent\Collection;

/** Query perbandingan lintas nagari untuk Teras domain utama. */
class PublicTerasAggregateService
{
    /** @return Collection<int, Nagari> */
    public function performaNagari(): Collection
    {
        return Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->withCount([
                'users as total_warga' => fn ($query) => $query->role('warga'),
                'umkmProfiles as total_umkm',
            ])
            ->addSelect([
                'total_penduduk' => Penduduk::selectRaw('COUNT(*)')
                    ->whereColumn('penduduk.nagari_id', 'nagaris.id'),
                'total_produk' => UmkmProduct::selectRaw('COUNT(*)')
                    ->join('umkm_profiles', 'umkm_profiles.id', '=', 'umkm_products.umkm_profile_id')
                    ->whereColumn('umkm_profiles.nagari_id', 'nagaris.id'),
                'skor_sdgs' => SdgAchievement::selectRaw('AVG(persentase)')
                    ->whereColumn('sdg_achievements.nagari_id', 'nagaris.id'),
                'modul_selesai' => UserModuleProgress::selectRaw('COUNT(*)')
                    ->join('users', 'users.id', '=', 'user_module_progress.user_id')
                    ->whereColumn('users.nagari_id', 'nagaris.id')
                    ->where('user_module_progress.status', ModuleProgressStatus::Completed->value),
                'status_idm' => IdmStatus::select('status')
                    ->whereColumn('idm_statuses.nagari_id', 'nagaris.id')
                    ->orderByDesc('tahun')
                    ->limit(1),
            ])
            ->orderBy('nama')
            ->get();
    }
}
