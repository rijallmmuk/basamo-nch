<?php

namespace App\Services;

use App\Models\Discussion;
use App\Models\Module;
use App\Models\User;
use App\Notifications\DiscussionReplied;

/**
 * Forum diskusi SLC selalu menempel pada satu MODUL. Tidak ada diskusi tingkat
 * pelatihan: tabel `discussions` hanya mengenal `module_id`.
 */
class SlcDiscussionService
{
    public function createModuleThread(Module $module, User $user, string $content): Discussion
    {
        return $module->discussions()->create([
            'user_id' => $user->getKey(),
            'parent_id' => null,
            'isi' => $content,
        ]);
    }

    public function replyToModuleThread(
        Module $module,
        Discussion $thread,
        User $user,
        string $content,
    ): Discussion {
        abort_unless(
            $thread->module_id === $module->getKey() && $thread->parent_id === null,
            404,
        );

        $reply = $module->discussions()->create([
            'user_id' => $user->getKey(),
            'parent_id' => $thread->getKey(),
            'isi' => $content,
        ]);

        $this->notifyThreadOwner($thread, $user);

        return $reply;
    }

    private function notifyThreadOwner(Discussion $thread, User $replier): void
    {
        if ($thread->user_id !== $replier->getKey() && $thread->user) {
            $thread->user->notify(new DiscussionReplied($thread, $replier->name));
        }
    }
}
