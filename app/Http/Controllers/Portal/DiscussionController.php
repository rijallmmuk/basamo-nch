<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Module;
use App\Models\User;
use App\Services\SlcDiscussionService;
use App\Services\SlcProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function __construct(
        private readonly SlcDiscussionService $discussionService,
        private readonly SlcProgressService $progressService,
    ) {}

    /**
     * Daftar thread diskusi sebuah modul (hanya sesama nagari).
     */
    public function index(Module $module): View
    {
        $user = $this->guardModule($module);

        $threads = $module->discussions()
            ->whereNull('parent_id')
            ->whereHas('user', fn ($q) => $q->where('nagari_id', $user->nagari_id))
            ->with(['user:id,name', 'user.media'])
            ->withCount('replies')
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(20);

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

        $discussion->load(['user:id,name', 'user.media']);
        $replies = $discussion->replies()
            ->with(['user:id,name', 'user.media'])
            ->oldest()
            ->paginate(30);

        return view('portal.modules.discuss.thread', compact('module', 'discussion', 'replies'));
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

        $thread = $this->discussionService->createModuleThread($module, $user, $data['isi']);

        // Tanpa notifikasi lonceng: pertanyaan baru cukup ditandai lewat badge angka
        // pada menu "Diskusi" di panel super admin (DiscussionResource::getNavigationBadge).

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

        $this->discussionService->replyToModuleThread(
            $module,
            $discussion,
            $user,
            $data['isi'],
        );

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

        if (! $module->isAccessibleToWarga($user)) {
            abort(404);
        }

        if (! $this->progressService->isModuleAccessible($user, $module)) {
            abort(404);
        }

        return $user;
    }

    /**
     * Thread valid bila milik modul ini, top-level, dan ditulis warga senagari.
     */
    private function threadVisibleToUser(Module $module, Discussion $discussion, User $user): bool
    {
        return $discussion->module_id === $module->id
            && $discussion->parent_id === null
            && $discussion->user?->nagari_id === $user->nagari_id;
    }
}
