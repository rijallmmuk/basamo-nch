<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * Policy ini mengatur desa_admin: hanya kelola warga di desanya.
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
        return $actor->isDesaAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $this->managesInDesa($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->isDesaAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $this->managesInDesa($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->getKey() !== $target->getKey()
            && $this->managesInDesa($actor, $target);
    }

    public function restore(User $actor, User $target): bool
    {
        return $this->managesInDesa($actor, $target);
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return $actor->getKey() !== $target->getKey()
            && $this->managesInDesa($actor, $target);
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
     * desa_admin hanya boleh mengelola warga di desanya sendiri.
     */
    private function managesInDesa(User $actor, User $target): bool
    {
        return $actor->isDesaAdmin()
            && $actor->desa_id !== null
            && $target->desa_id === $actor->desa_id
            && in_array($target->role, self::MANAGEABLE_ROLES, true);
    }
}
