<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * Policy ini mengatur nagari_admin: hanya kelola warga di nagarinya.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * @return list<string>
     */
    private const MANAGEABLE_ROLES = ['warga'];

    public function viewAny(User $actor): bool
    {
        return $actor->isNagariAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $this->managesInNagari($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->isNagariAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $this->managesInNagari($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->getKey() !== $target->getKey()
            && $this->managesInNagari($actor, $target);
    }

    public function restore(User $actor, User $target): bool
    {
        return $this->managesInNagari($actor, $target);
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return $actor->getKey() !== $target->getKey()
            && $this->managesInNagari($actor, $target);
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    /**
     * nagari_admin hanya boleh mengelola warga di nagarinya sendiri.
     */
    private function managesInNagari(User $actor, User $target): bool
    {
        return $actor->isNagariAdmin()
            && $actor->nagari_id !== null
            && $target->nagari_id === $actor->nagari_id
            && in_array($target->role, self::MANAGEABLE_ROLES, true);
    }
}
