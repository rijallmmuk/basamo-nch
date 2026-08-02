<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Discussion;
use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Moderasi diskusi modul. superadmin dilewatkan via Gate::before (akses penuh, semua nagari).
 * operator hanya boleh memoderasi diskusi yang ditulis warga nagarinya sendiri.
 * Diskusi dibuat warga lewat portal — admin tak membuat/menyunting isi (hanya pin & hapus).
 */
class DiscussionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->isPengajar();
    }

    public function view(User $user, Discussion $discussion): bool
    {
        return $this->moderates($user, $discussion);
    }

    public function create(User $user): bool
    {
        return false;
    }

    /** Sematkan/lepas sematan (pin) = update is_pinned. */
    public function update(User $user, Discussion $discussion): bool
    {
        return $this->moderates($user, $discussion);
    }

    public function reply(User $user, Discussion $discussion): bool
    {
        return $this->moderates($user, $discussion);
    }

    public function delete(User $user, Discussion $discussion): bool
    {
        return $this->moderates($user, $discussion);
    }

    public function restore(User $user, Discussion $discussion): bool
    {
        return $this->moderates($user, $discussion);
    }

    public function forceDelete(User $user, Discussion $discussion): bool
    {
        return $this->moderates($user, $discussion);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * operator: hanya diskusi warga nagarinya. pengajar: diskusi modul
     * dari program yang ditugaskan kepadanya.
     */
    private function moderates(User $user, Discussion $discussion): bool
    {
        if ($user->isOperator()) {
            if ($user->nagari_id === null) {
                return false;
            }

            return Discussion::query()
                ->whereKey($discussion->getKey())
                ->whereHas('user', fn ($users) => $users->where('nagari_id', $user->nagari_id))
                ->exists();
        }

        if (! $user->isPengajar()) {
            return false;
        }

        return Pelatihan::query()
            ->manageableBy($user)
            ->whereHas('modules', fn ($m) => $m->whereKey($discussion->module_id))
            ->exists();
    }
}
