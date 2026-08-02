<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\EwsDevice;
use App\Models\Nagari;
use App\Services\Ews\EwsPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IotCatalogController extends Controller
{
    public function __construct(private readonly EwsPanelService $ews) {}

    public function index(Request $request): View
    {
        $nagariOptions = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->whereHas('ewsDevices', fn ($query) => $query->where('aktif', true))
            ->orderBy('nama')
            ->get(['id', 'nama', 'slug', 'kabupaten']);

        $selectedNagariId = $request->integer('nagari');
        if (! $nagariOptions->contains('id', $selectedNagariId)) {
            $selectedNagariId = 0;
        }

        $devices = EwsDevice::query()
            ->siapPakai()
            ->when($selectedNagariId, fn ($query) => $query->where('nagari_id', $selectedNagariId))
            ->with(['nagari:id,nama,slug,kabupaten,status', 'pembacaanTerakhir'])
            ->orderBy('nagari_id')
            ->orderBy('nama_lokasi')
            ->paginate(9)
            ->withQueryString();

        $panels = $devices->getCollection()
            ->mapWithKeys(fn (EwsDevice $device): array => [
                $device->getKey() => $this->ews->untukPerangkat($device),
            ]);

        return view('public.iot.index', [
            'devices' => $devices,
            'panels' => $panels,
            'nagariOptions' => $nagariOptions,
            'selectedNagariId' => $selectedNagariId,
        ]);
    }
}
