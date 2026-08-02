<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Services\PublicOverviewService;
use App\Services\PublicTerasAggregateService;
use App\Services\PublicTerasDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TerasCatalogController extends Controller
{
    public function __construct(
        private readonly PublicTerasDataService $data,
        private readonly PublicOverviewService $overview,
        private readonly PublicTerasAggregateService $aggregate,
    ) {}

    public function index(Request $request): View
    {
        $nagariOptions = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->orderBy('nama')
            ->get(['id', 'nama', 'slug', 'kabupaten']);

        $selectedNagari = $request->integer('nagari')
            ? Nagari::query()
                ->where('status', ActiveStatus::Active)
                ->find($request->integer('nagari'))
            : null;

        if ($selectedNagari) {
            return view('public.nagari.teras', [
                ...$this->data->untukNagari($selectedNagari, $request),
                'globalTeras' => true,
                'nagariOptions' => $nagariOptions,
            ]);
        }

        return view('public.teras.index', [
            'overview' => $this->overview->overview(),
            'performa' => $this->aggregate->performaNagari(),
            'nagariOptions' => $nagariOptions,
        ]);
    }
}
