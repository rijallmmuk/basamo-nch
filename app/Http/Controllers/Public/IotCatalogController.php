<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Support\PublicNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IotCatalogController extends Controller
{
    /** Alamat lama dipertahankan untuk bookmark; seluruh data kini berada di Teras. */
    public function index(Request $request): RedirectResponse
    {
        $nagari = $request->integer('nagari')
            ? Nagari::query()
                ->where('status', ActiveStatus::Active)
                ->find($request->integer('nagari'))
            : null;

        $tujuan = $nagari
            ? PublicNavigation::rute('public.nagari.teras', $nagari)
            : route('public.teras');

        return redirect()->to($tujuan.'#iot', 301);
    }
}
