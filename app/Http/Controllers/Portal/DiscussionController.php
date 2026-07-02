<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Module;
use App\Models\User;
use App\Notifications\DiscussionReplied;
use App\Services\LmsPointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function __construct(private readonly LmsPointService $pointService) {}

    /**
     * Daftar thread diskusi sebuah modul (hanya sesama desa).
     */
    public function index(Module $module): View
    {
        $user = $this->guardModule($module);

        $threads = $module->discussions()
            ->whereNull('parent_id')
            ->whereHas('user', fn ($q) => $q->where('desa_id', $user->desa_id))
            ->with(['user:id,name', 'user.media'])
            ->withCount('replies')
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        return view('portal.modules.discuss.index', compact('module', 'threads'));
    }

    /**
     * Detail satu thread beserta balasannya.
     */
    public function show(Module $module, Discussion $discussion): View|RedirectResponse
    {
        $user = $this->guardModule($module);

        if (! $this->threadVisibleToUser($module, $discussion, $user)) {
            abort(404);
        }

        $discussion->load([
            'user:id,name',
            'user.media',
            'replies' => fn ($q) => $q->with(['user:id,name', 'user.media'])->oldest(),
        ]);

        return view('portal.modules.discuss.thread', compact('module', 'discussion'));
    }

    /**
     * Buat thread pertanyaan baru.
     */
    public function store(Request $request, Module $module): RedirectResponse
    {
        $user = $this->guardModule($module);

        $data = $request->validate([
            'isi' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $thread = $module->discussions()->create([
            'user_id' => $user->id,
            'isi' => $data['isi'],
        ]);

        // XP partisipasi diskusi (idempotent — sekali per modul, posting pertama).
        $this->pointService->awardDiscussionParticipation($user, $module);

        return redirect()
            ->route('portal.modules.discuss.show', [$module, $thread])
            ->with('success', 'Pertanyaan kamu sudah dikirim.');
    }

    /**
     * Balas sebuah thread.
     */
    public function reply(Request $request, Module $module, Discussion $discussion): RedirectResponse
    {
        $user = $this->guardModule($module);

        if (! $this->threadVisibleToUser($module, $discussion, $user)) {
            abort(404);
        }

        $data = $request->validate([
            'isi' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $module->discussions()->create([
            'user_id' => $user->id,
            'parent_id' => $discussion->id,
            'isi' => $data['isi'],
        ]);

        // Beri tahu penanya bahwa pertanyaannya dibalas (kecuali membalas thread sendiri).
        if ($discussion->user_id !== $user->id && $discussion->user) {
            $discussion->user->notify(new DiscussionReplied($discussion, $user->name));
        }

        // XP partisipasi diskusi (idempotent — sekali per modul, posting pertama).
        $this->pointService->awardDiscussionParticipation($user, $module);

        return redirect()
            ->route('portal.modules.discuss.show', [$module, $discussion])
            ->with('success', 'Balasan kamu sudah dikirim.');
    }

    /**
     * Pastikan modul published & dapat diakses user, kembalikan user aktif.
     */
    private function guardModule(Module $module): User
    {
        $user = auth()->user();

        if ($module->status !== ModuleStatus::Published ||
            ($module->desa_id !== null && $module->desa_id !== $user->desa_id)) {
            abort(404);
        }

        return $user;
    }

    /**
     * Thread valid bila milik modul ini, top-level, dan ditulis warga sedesa.
     */
    private function threadVisibleToUser(Module $module, Discussion $discussion, User $user): bool
    {
        return $discussion->module_id === $module->id
            && $discussion->parent_id === null
            && $discussion->user?->desa_id === $user->desa_id;
    }
}
