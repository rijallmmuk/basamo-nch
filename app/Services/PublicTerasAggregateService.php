<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Models\IdmStatus;
use App\Models\EwsDevice;
use App\Models\Nagari;
use App\Models\Penduduk;
use App\Models\SdgAchievement;
use App\Models\UmkmProduct;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Query perbandingan lintas nagari untuk Teras domain utama. */
class PublicTerasAggregateService
{
    /** @return Collection<int, Nagari> */
    public function performaNagari(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Satu definisi rekap per nagari untuk Teras dan dashboard lintas nagari.
     * Seluruh baris dan metrik di sini menggambarkan ekosistem yang AKTIF serta
     * dapat diakses sekarang, bukan seluruh riwayat record administratif.
     *
     * @return Builder<Nagari>
     */
    public function query(): Builder
    {
        $wargaAktif = User::query()
            ->select('users.id')
            ->role('warga')
            ->where('users.status', ActiveStatus::Active);

        return Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->withCount([
                'users as total_warga' => fn ($query) => $query
                    ->role('warga')
                    ->where('users.status', ActiveStatus::Active),
                'umkmProfiles as total_umkm' => fn ($query) => $query
                    ->where('umkm_profiles.status', ActiveStatus::Active),
            ])
            ->addSelect([
                'total_penduduk' => Penduduk::selectRaw('COUNT(*)')
                    ->whereColumn('penduduk.nagari_id', 'nagaris.id'),
                'total_produk' => UmkmProduct::selectRaw('COUNT(*)')
                    ->join('umkm_profiles', 'umkm_profiles.id', '=', 'umkm_products.umkm_profile_id')
                    ->whereNull('umkm_profiles.deleted_at')
                    ->where('umkm_profiles.status', ActiveStatus::Active)
                    ->whereColumn('umkm_profiles.nagari_id', 'nagaris.id'),
                // Semua 18 poin selalu menjadi penyebut. Poin yang belum ada
                // bernilai nol, sama dengan SdgScoringService dan Teras detail.
                'skor_sdgs' => SdgAchievement::selectRaw(
                    'SUM(CASE WHEN persentase < 0 THEN 0 WHEN persentase > 100 THEN 100 ELSE COALESCE(persentase, 0) END) / 18'
                )
                    ->whereColumn('sdg_achievements.nagari_id', 'nagaris.id'),
                'sdg_terisi' => SdgAchievement::selectRaw('COUNT(*)')
                    ->whereNotNull('persentase')
                    ->whereColumn('sdg_achievements.nagari_id', 'nagaris.id'),
                'modul_selesai' => UserModuleProgress::selectRaw('COUNT(*)')
                    ->join('users', 'users.id', '=', 'user_module_progress.user_id')
                    ->whereColumn('users.nagari_id', 'nagaris.id')
                    ->whereIn('users.id', $wargaAktif)
                    ->where('user_module_progress.status', ModuleProgressStatus::Completed->value),
                'warga_belajar' => UserModuleProgress::selectRaw('COUNT(DISTINCT user_module_progress.user_id)')
                    ->join('users', 'users.id', '=', 'user_module_progress.user_id')
                    ->whereColumn('users.nagari_id', 'nagaris.id')
                    ->whereIn('users.id', $wargaAktif),
                'total_iot' => EwsDevice::selectRaw('COUNT(*)')
                    ->whereColumn('ews_devices.nagari_id', 'nagaris.id')
                    ->where('ews_devices.aktif', true),
                'status_idm' => IdmStatus::select('status')
                    ->whereColumn('idm_statuses.nagari_id', 'nagaris.id')
                    ->orderByDesc('tahun')
                    ->limit(1),
            ])
            ->orderBy('nama');
    }
}
