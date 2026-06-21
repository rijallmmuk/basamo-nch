<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Discussion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Moderasi diskusi. super_admin dilewatkan via Gate::before (akses penuh, semua desa).
 * desa_admin hanya boleh memoderasi diskusi yang ditulis warga desanya sendiri.
 * Diskusi dibuat warga lewat portal — admin tak membuat/menyunting isi (hanya pin & hapus).
 */
class DiscussionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isDesaAdmin();
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

    /** desa_admin: hanya diskusi warga desanya. (super_admin lewat Gate::before.) */
    private function moderates(User $user, Discussion $discussion): bool
    {
        return $user->isDesaAdmin()
            && $discussion->user?->desa_id === $user->desa_id;
    }
}
