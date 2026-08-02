<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * superadmin dilewatkan via Gate::before (akses penuh).
 * operator hanya mengelola profil usaha di nagarinya (cek per-record `nagari_id`,
 * mandiri — tak bergantung pada scoping query saja).
 */
class UmkmProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->usesUmkmSelfService() || $user->hasRole('dpmd');
    }

    public function view(User $user, UmkmProfile $profile): bool
    {
        return $user->hasRole('dpmd') || $this->ownsProfile($user, $profile) || $this->managesInNagari($user, $profile);
    }

    public function create(User $user): bool
    {
        return $user->usesUmkmSelfService()
            && ! $user->umkmProfile()->withTrashed()->exists();
    }

    public function update(User $user, UmkmProfile $profile): bool
    {
        return $this->ownsProfile($user, $profile);
    }

    public function delete(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInNagari($user, $profile);
    }

    public function restore(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInNagari($user, $profile);
    }

    public function forceDelete(User $user, UmkmProfile $profile): bool
    {
        return $this->managesInNagari($user, $profile);
    }

    /** Aksi massal (bulk) TIDAK dipakai di panel (klik baris → aksi per-record). */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /** operator hanya profil usaha di nagarinya sendiri. */
    private function managesInNagari(User $user, UmkmProfile $profile): bool
    {
        return $user->isOperator()
            && $user->nagari_id !== null
            && $profile->nagari_id === $user->nagari_id;
    }

    private function ownsProfile(User $user, UmkmProfile $profile): bool
    {
        return $user->usesUmkmSelfService() && $profile->user_id === $user->id;
    }
}
