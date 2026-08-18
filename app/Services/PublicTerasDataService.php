<?php

namespace App\Services;

use App\Models\Nagari;
use App\Services\Ews\EwsPanelService;
use App\Services\Sdg\SdgScoringService;
use Illuminate\Http\Request;

/** Menyusun satu sumber data untuk Teras subdomain dan Teras domain utama. */
class PublicTerasDataService
{
    public function __construct(
        private readonly SdgScoringService $sdg,
        private readonly BmkgWeatherService $weather,
        private readonly PublicOverviewService $overview,
        private readonly EwsPanelService $ews,
        private readonly PublicTerasSummaryService $summary,
    ) {}

    /** @return array<string, mixed> */
    public function untukNagari(Nagari $nagari, Request $request): array
    {
        $nagari->loadMissing(['media', 'latestIdmStatus.indicators']);

        $poinTerbuka = $request->integer('poin');
        $poinTerbuka = $poinTerbuka >= 1 && $poinTerbuka <= 18 ? $poinTerbuka : null;

        return [
            'nagari' => $nagari,
            'sdgSkor' => $this->sdg->skorNagari($nagari->id),
            'sdgPilar' => $this->sdg->capaianPerPilar($nagari->id, $poinTerbuka),
            'poinTerbuka' => $poinTerbuka,
            'idm' => $nagari->latestIdmStatus,
            'cuaca' => $this->weather->prakiraan($nagari),
            'overview' => $this->overview->overview($nagari),
            'ringkasanBelajar' => $this->summary->belajar($nagari),
            'ringkasanLingkungan' => $this->summary->lingkungan($nagari),
            'ews' => $this->ews->untukNagari($nagari),
        ];
    }
}
