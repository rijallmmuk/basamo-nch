<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * desa_admin hanya mengelola profil usaha di desanya (cek per-record `desa_id`,
 * mandiri — tak bergantung pada scoping query saja).
 */
class UmkmProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function view(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInDesa($user, $profile);
    }

    public function create(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function update(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInDesa($user, $profile);
    }

    public function delete(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInDesa($user, $profile);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restore(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInDesa($user, $profile);
    }

    public function forceDelete(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInDesa($user, $profile);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    /** desa_admin hanya profil usaha di desanya sendiri. */
    private function managesInDesa(User $user, UmkmProfile $profile): bool
    {
        return $user->isDesaAdmin()
            && $user->desa_id !== null
            && $profile->desa_id === $user->desa_id;
    }
}
