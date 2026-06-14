<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    /**
     * Daftar thread diskusi sebuah modul (hanya sesama nagari).
     */
    public function index(Module $module): View
    {
        $user = $this->guardModule($module);

        $threads = $module->discussions()
            ->whereNull('parent_id')
            ->whereHas('user', fn ($q) => $q->where('nagari_id', $user->nagari_id))
            ->with(['user:id,name'])
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
            'replies' => fn ($q) => $q->with('user:id,name')->oldest(),
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
            'body' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $thread = $module->discussions()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

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
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $module->discussions()->create([
            'user_id' => $user->id,
            'parent_id' => $discussion->id,
            'body' => $data['body'],
        ]);

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

        if ($module->status !== 'published' ||
            ($module->nagari_id !== null && $module->nagari_id !== $user->nagari_id)) {
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
