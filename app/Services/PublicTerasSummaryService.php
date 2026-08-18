<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Models\Certificate;
use App\Models\EwsDevice;
use App\Models\Nagari;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\Ews\EwsPanelService;
use Illuminate\Database\Eloquent\Builder;

/** Ringkasan data operasional yang aman ditampilkan pada Teras Nagari. */
final class PublicTerasSummaryService
{
    /**
     * Rekap aktivitas belajar, bukan jumlah akun. Tidak ada identitas atau progres
     * seorang warga yang dikirim ke halaman publik.
     *
     * @return list<array{label: string, value: int, icon: string, description: string, tone: string}>
     */
    public function belajar(?Nagari $nagari = null): array
    {
        $warga = User::query()
            ->select('users.id')
            ->role('warga')
            ->where('users.status', ActiveStatus::Active)
            ->whereHas('nagari', fn (Builder $query) => $query
                ->where('status', ActiveStatus::Active)
                ->when($nagari, fn (Builder $scope) => $scope->whereKey($nagari->getKey())));

        $progres = UserModuleProgress::query()->whereIn('user_id', clone $warga);

        return [
            [
                'label' => 'Warga Belajar',
                'value' => (clone $progres)->distinct()->count('user_id'),
                'icon' => 'heroicon-o-user-group',
                'description' => 'Warga aktif yang memiliki aktivitas belajar',
                'tone' => 'info',
            ],
            [
                'label' => 'Sedang Belajar',
                'value' => (clone $progres)
                    ->where('status', ModuleProgressStatus::InProgress->value)
                    ->distinct()
                    ->count('user_id'),
                'icon' => 'heroicon-o-arrow-path',
                'description' => 'Warga dengan modul yang masih berjalan',
                'tone' => 'warning',
            ],
            [
                'label' => 'Modul Diselesaikan',
                'value' => (clone $progres)
                    ->where('status', ModuleProgressStatus::Completed->value)
                    ->count(),
                'icon' => 'heroicon-o-check-circle',
                'description' => 'Akumulasi penyelesaian modul',
                'tone' => 'success',
            ],
            [
                'label' => 'Sertifikat Terbit',
                'value' => Certificate::query()->whereIn('user_id', clone $warga)->count(),
                'icon' => 'heroicon-o-academic-cap',
                'description' => 'Sertifikat pelatihan yang telah diterbitkan',
                'tone' => 'secondary',
            ],
        ];
    }

    /**
     * Ringkasan cakupan cuaca dan keadaan perangkat IoT dari data tersimpan.
     * Halaman utama tidak menembak layanan perangkat ataupun BMKG satu per satu.
     *
     * @return list<array{label: string, value: int|string, icon: string, description: string, tone: string}>
     */
    public function lingkungan(?Nagari $nagari = null): array
    {
        $nagariCuaca = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->whereNotNull('wilayah_kode')
            ->where('wilayah_kode', '!=', '')
            ->when($nagari, fn (Builder $query) => $query->whereKey($nagari->getKey()))
            ->count();

        $devices = EwsDevice::query()
            ->siapPakai()
            ->when($nagari, fn (Builder $query) => $query->where('nagari_id', $nagari->getKey()))
            ->with('pembacaanTerakhir')
            ->get();

        $batasBasi = now()->subMinutes(EwsPanelService::BATAS_BASI_MENIT);
        $terkini = $devices->filter(fn (EwsDevice $device): bool =>
            (bool) $device->pembacaanTerakhir?->terhubung
            && $device->pembacaanTerakhir->direkam_pada?->gte($batasBasi)
        );
        $perluPerhatian = $terkini->filter(fn (EwsDevice $device): bool =>
            $device->pembacaanTerakhir?->status()->perluPerhatian() ?? false
        )->count();

        return [
            [
                'label' => 'Cakupan Cuaca BMKG',
                'value' => $nagari ? ($nagariCuaca > 0 ? 'Terdaftar' : 'Belum terdaftar') : $nagariCuaca,
                'icon' => 'heroicon-o-cloud',
                'description' => $nagari
                    ? 'Kesiapan kode wilayah untuk prakiraan cuaca'
                    : 'Nagari dengan kode wilayah untuk prakiraan',
                'tone' => 'info',
            ],
            [
                'label' => 'Titik Pantau IoT',
                'value' => $devices->count(),
                'icon' => 'heroicon-o-cpu-chip',
                'description' => 'Perangkat EWS yang aktif',
                'tone' => 'secondary',
            ],
            [
                'label' => 'Data IoT Terkini',
                'value' => $terkini->count(),
                'icon' => 'heroicon-o-signal',
                'description' => 'Terhubung dan merekam dalam 20 menit terakhir',
                'tone' => 'success',
            ],
            [
                'label' => 'Perlu Perhatian',
                'value' => $perluPerhatian,
                'icon' => 'heroicon-o-exclamation-triangle',
                'description' => 'Titik terkini berstatus waspada, siaga, atau awas',
                'tone' => $perluPerhatian > 0 ? 'warning' : 'primary',
            ],
        ];
    }
}
