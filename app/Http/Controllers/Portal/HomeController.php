<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\LmsProgressService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly LmsProgressService $progressService) {}

    public function index(): View
    {
        $user = auth()->user();

        $modules = Module::where('status', 'published')
            ->where(function ($q) use ($user) {
                $q->whereNull('nagari_id')
                    ->orWhere('nagari_id', $user->nagari_id);
            })
            ->with(['progress' => fn ($q) => $q->where('user_id', $user->id)])
            ->withCount('pages')
            ->orderBy('order')
            ->get();

        $statusMap = $modules->mapWithKeys(
            fn ($m) => [$m->id => $this->progressService->getModuleStatus($user, $m)]
        );

        $priorityOrder = ['in_progress' => 0, 'available' => 1, 'completed' => 2, 'locked' => 3];

        $featured = $modules->sortBy(function ($m) use ($statusMap, $priorityOrder) {
            $priority = $priorityOrder[$statusMap[$m->id] ?? 'available'] ?? 4;

            return [$priority, $m->order];
        })->take(3)->values();

        return view('portal.home', compact('featured', 'statusMap', 'modules'));
    }
}
