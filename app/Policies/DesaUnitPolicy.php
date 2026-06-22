<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DesaUnit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * desa_admin hanya mengelola sub-unit (wilayah) desanya sendiri (cek per-record
 * `desa_id`, mandiri — tak bergantung pada scoping query saja).
 */
class DesaUnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function view(User $user, DesaUnit $desaUnit): bool
    {
        return $this->managesInDesa($user, $desaUnit);
    }

    public function create(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function update(User $user, DesaUnit $desaUnit): bool
    {
        return $this->managesInDesa($user, $desaUnit);
    }

    public function delete(User $user, DesaUnit $desaUnit): bool
    {
        return $this->managesInDesa($user, $desaUnit);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restore(User $user, DesaUnit $desaUnit): bool
    {
        return $this->managesInDesa($user, $desaUnit);
    }

    public function forceDelete(User $user, DesaUnit $desaUnit): bool
    {
        return $this->managesInDesa($user, $desaUnit);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    /** desa_admin hanya sub-unit desanya sendiri. */
    private function managesInDesa(User $user, DesaUnit $desaUnit): bool
    {
        return $user->isDesaAdmin()
            && $user->desa_id !== null
            && $desaUnit->desa_id === $user->desa_id;
    }
}
