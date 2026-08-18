<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Enums\DimensiIdm;
use App\Enums\StatusIdm;
use App\Http\Controllers\Controller;
use App\Models\IdmStatus;
use App\Models\Nagari;
use App\Models\UmkmProfile;
use App\Services\PublicOverviewService;
use App\Services\BmkgWeatherService;
use App\Services\Ews\EwsPanelService;
use App\Services\PublicTerasAggregateService;
use App\Services\PublicTerasSummaryService;
use App\Services\Sdg\SdgScoringService;
use App\Support\PublicNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerasCatalogController extends Controller
{
    /** @var array<string, string> */
    private const TABS = [
        'ringkasan' => 'Ringkasan',
        'penduduk' => 'Penduduk',
        'pembangunan' => 'Pembangunan',
        'pembelajaran' => 'Pembelajaran',
        'ekonomi' => 'Ekonomi',
        'lingkungan' => 'Cuaca & IoT',
    ];

    public function __construct(
        private readonly PublicOverviewService $overview,
        private readonly PublicTerasAggregateService $aggregate,
        private readonly PublicTerasSummaryService $summary,
        private readonly BmkgWeatherService $weather,
        private readonly EwsPanelService $ews,
        private readonly SdgScoringService $sdg,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        // Alamat lama `?nagari=id` tidak lagi merender profil di URL global.
        // Arahkan ke alamat kanonis nagari agar bookmark lama tetap berfungsi.
        $selectedNagari = $request->integer('nagari')
            ? Nagari::query()
                ->where('status', ActiveStatus::Active)
                ->find($request->integer('nagari'))
            : null;

        if ($selectedNagari) {
            return redirect()->to(
                PublicNavigation::rute('public.nagari.teras', $selectedNagari),
                301,
            );
        }

        $performa = $this->aggregate->performaNagari();
        $kabupatens = $performa->pluck('kabupaten')->filter()->unique()->sort()->values();

        $idmCounts = $performa->groupBy(function ($nagari) {
            return $nagari->status_idm
                ? (StatusIdm::tryFrom((string) $nagari->status_idm)?->label() ?? (string) $nagari->status_idm)
                : 'Belum terdata';
        })->map->count();

        $nagariBerSdgs = $performa->filter(fn ($n) => (int) $n->sdg_terisi > 0);
        $rataRataSdgs = $nagariBerSdgs->isNotEmpty()
            ? round($nagariBerSdgs->avg('skor_sdgs'), 1)
            : null;

        return view('public.teras.index', [
            'performa' => $performa,
            'kabupatens' => $kabupatens,
            'idmCounts' => $idmCounts,
            'rataRataSdgs' => $rataRataSdgs,
            'nagariBerSdgsCount' => $nagariBerSdgs->count(),
        ]);
    }

    /** Kontrak data lazy-load untuk setiap tab modal Teras utama. */
    public function nagariData(Request $request, Nagari $nagari): JsonResponse
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        $tab = (string) $request->query('tab', 'ringkasan');
        abort_unless(array_key_exists($tab, self::TABS), 404);

        $data = match ($tab) {
            'ringkasan' => $this->ringkasanData($nagari),
            'penduduk' => collect($this->overview->overview($nagari, lengkap: true))->only([
                'metrics', 'gender', 'ageGroups', 'education', 'occupations',
            ])->all(),
            'pembangunan' => $this->pembangunanData($nagari),
            'pembelajaran' => $this->pembelajaranData($nagari),
            'ekonomi' => $this->ekonomiData($nagari),
            'lingkungan' => [
                'metrics' => $this->summary->lingkungan($nagari),
                'cuaca' => $this->weather->prakiraan($nagari),
                'ews' => $this->ews->semuaUntukNagari($nagari)->map(function (array $panel): array {
                    $reading = $panel['pembacaan'];

                    return [
                        'nama' => $panel['device']->namaTampil(),
                        'status' => $panel['status']->getLabel(),
                        'terhubung' => $panel['terhubung'],
                        'basi' => $panel['basi'],
                        'pembacaan' => [
                            'tinggi_air' => $reading?->tinggi_air,
                            'curah_hujan' => $reading?->curah_hujan,
                            'ph_air' => $reading?->ph_air,
                            'getaran' => $reading?->getaran,
                            'direkam_pada' => $reading?->direkam_pada?->toIso8601String(),
                        ],
                        'tren' => $panel['tren'],
                    ];
                })->values(),
            ],
        };

        $tabs = collect(self::TABS)->map(fn (string $label, string $key): array => [
            'key' => $key,
            'label' => $label,
            'url' => route('public.teras.nagari.data', $nagari).'?tab='.$key,
        ])->values();

        return response()->json([
            'nagari' => [
                'slug' => $nagari->slug,
                'nama' => $nagari->nama_lengkap,
                'lokasi' => collect([$nagari->kecamatan, $nagari->kabupaten, $nagari->provinsi])->filter()->implode(', '),
                'url_teras' => PublicNavigation::rute('public.nagari.teras', $nagari),
            ],
            'tab' => $tab,
            'tabs' => $tabs,
            'data' => $data,
        ])->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function ringkasanData(Nagari $nagari): array
    {
        $overview = $this->overview->overview($nagari, lengkap: false);
        $overview['metrics'] = collect($overview['metrics'])
            ->whereIn('label', ['Penduduk', 'UMKM', 'Produk'])
            ->values()
            ->all();

        $idm = IdmStatus::query()
            ->where('nagari_id', $nagari->getKey())
            ->latest('tahun')
            ->first();

        $statusIdm = $idm
            ? ($idm->statusEnum() ?? StatusIdm::tryFrom((string) $idm->status))
            : null;

        $cuaca = $this->weather->prakiraan($nagari);

        return [
            'overview' => $overview,
            'status_idm' => $statusIdm?->label() ?? $idm?->status,
            'skor_idm' => $idm?->skor !== null ? (float) $idm->skor : null,
            'cuaca_ringkas' => $cuaca['saat_ini'] ?? null,
            'lokasi_cuaca' => $cuaca['lokasi'] ?? null,
            'pembelajaran' => $this->summary->belajar($nagari),
            'lingkungan' => $this->summary->lingkungan($nagari),
        ];
    }

    /** @return array<string, mixed> */
    private function pembelajaranData(Nagari $nagari): array
    {
        return [
            'katalog' => collect($this->overview->overview($nagari, lengkap: false)['metrics'])
                ->whereIn('label', ['Pelatihan', 'Modul'])
                ->values()
                ->all(),
            'aktivitas' => $this->summary->belajar($nagari),
            'url_katalog' => PublicNavigation::rute('public.nagari.slc', $nagari),
        ];
    }

    /** @return array<string, mixed> */
    private function pembangunanData(Nagari $nagari): array
    {
        $overview = $this->overview->overview($nagari, lengkap: false);
        if ($overview['idm']['latest']) {
            $status = (string) $overview['idm']['latest']['status'];
            $overview['idm']['latest']['status'] = StatusIdm::tryFrom($status)?->label() ?? $status;
        }

        $pilar = $this->sdg->capaianPerPilar($nagari->getKey())->map(fn (array $kelompok): array => [
            'nama' => $kelompok['pilar']?->nama ?? 'Tanpa pilar',
            'warna' => $kelompok['pilar']?->warna ?: '#003857',
            'skor' => $kelompok['skor'],
            'poin' => $kelompok['poin']->map(fn (array $poin, int $nomor): array => [
                'nomor' => $nomor,
                'nama' => $poin['goal']->nama,
                'nilai' => $poin['nilai'],
                'terisi' => $poin['terisi'],
                'warna' => $poin['goal']->warna,
                'jumlah_sasaran' => $poin['jumlah_sasaran'] ?? 0,
                'jumlah_indikator' => $poin['jumlah_indikator'] ?? 0,
            ])->values(),
        ])->values();

        $idm = IdmStatus::query()
            ->where('nagari_id', $nagari->getKey())
            ->latest('tahun')
            ->with(['indicators' => fn ($q) => $q->orderBy('nomor')])
            ->first();

        $statusIdm = $idm
            ? ($idm->statusEnum() ?? StatusIdm::tryFrom((string) $idm->status))
            : null;

        $targetStatus = $idm?->target_status
            ? (StatusIdm::tryFrom((string) $idm->target_status)?->label() ?? $idm->target_status)
            : null;

        $idmDetail = $idm ? [
            'tahun' => $idm->tahun,
            'skor' => (float) $idm->skor,
            'status' => $statusIdm?->label() ?? $idm->status,
            'target_status' => $targetStatus,
            'skor_minimal' => $idm->skor_minimal !== null ? (float) $idm->skor_minimal : null,
            'penambahan' => $idm->penambahan !== null ? (float) $idm->penambahan : null,
            'dimensi' => [
                ['label' => 'Ketahanan Sosial (IKS)', 'skor' => (float) $idm->skor_iks, 'bar' => 'bg-sky-500'],
                ['label' => 'Ketahanan Ekonomi (IKE)', 'skor' => (float) $idm->skor_ike, 'bar' => 'bg-amber-500'],
                ['label' => 'Ketahanan Lingkungan (IKL)', 'skor' => (float) $idm->skor_ikl, 'bar' => 'bg-emerald-500'],
            ],
            'indicators' => $idm->indicators->map(fn ($ind) => [
                'dimensi' => $ind->dimensi instanceof DimensiIdm ? $ind->dimensi->label() : (string) $ind->dimensi,
                'indikator' => $ind->indikator,
                'keterangan' => $ind->keterangan,
                'kegiatan' => $ind->kegiatan,
                'pelaksana' => $ind->pelaksana,
                'skor' => (int) $ind->skor,
                'nilai' => $ind->nilai !== null ? (float) $ind->nilai : null,
            ])->values()->all(),
        ] : null;

        return [
            'sdgs' => $overview['sdgs'],
            'idm' => $overview['idm'],
            'idm_detail' => $idmDetail,
            'sdg_pilar' => $pilar,
        ];
    }

    /** @return array<string, mixed> */
    private function ekonomiData(Nagari $nagari): array
    {
        $usaha = UmkmProfile::query()
            ->where('nagari_id', $nagari->getKey())
            ->where('status', ActiveStatus::Active)
            ->withCount('products as produk_count')
            ->orderByDesc('produk_count')
            ->orderBy('nama_usaha')
            ->limit(8)
            ->get()
            ->map(fn (UmkmProfile $profile): array => [
                'nama' => $profile->nama_usaha,
                'produk' => (int) $profile->produk_count,
                'url' => PublicNavigation::rute('public.nagari.umkm.etalase', $nagari, ['umkmProfile' => $profile]),
            ]);

        return [
            'metrics' => collect($this->overview->overview($nagari, lengkap: false)['metrics'])
                ->whereIn('label', ['UMKM', 'Produk'])
                ->values()
                ->all(),
            'usaha' => $usaha,
            'url_direktori' => PublicNavigation::rute('public.nagari.umkm', $nagari),
        ];
    }
}
