<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Services\PublicSlcCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SlcCatalogController extends Controller
{
    public function __construct(private readonly PublicSlcCatalogService $catalog) {}

    public function global(Request $request): View
    {
        return $this->render($request);
    }

    public function nagari(Request $request, Nagari $nagari): View
    {
        abort_unless($nagari->status === ActiveStatus::Active, 404);

        return $this->render($request, $nagari);
    }

    private function render(Request $request, ?Nagari $nagari = null): View
    {
        return view('public.slc.index', [
            ...$this->catalog->catalog($nagari, $request->query()),
            'nagari' => $nagari,
        ]);
    }
}
