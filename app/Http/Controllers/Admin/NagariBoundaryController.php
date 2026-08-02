<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Services\WilayahBoundaryService;
use Illuminate\Http\JsonResponse;

/** GeoJSON batas wilayah untuk peta read-only di panel pengelolaan. */
class NagariBoundaryController extends Controller
{
    public function __construct(private readonly WilayahBoundaryService $boundaries) {}

    public function show(): JsonResponse
    {
        $user = auth()->user();

        // Ter-scope ketat: hanya operator nagari, hanya nagarinya sendiri (tanpa parameter → tanpa IDOR).
        abort_unless(($user?->isOperator() ?? false) && $user->nagari, 403);

        return $this->responseFor($user->nagari);
    }

    public function showForNagari(Nagari $nagari): JsonResponse
    {
        $this->authorize('view', $nagari);

        return $this->responseFor($nagari);
    }

    private function responseFor(Nagari $nagari): JsonResponse
    {
        return response()
            ->json($this->boundaries->forNagari($nagari))
            ->header('Cache-Control', 'private, max-age=300');
    }
}
