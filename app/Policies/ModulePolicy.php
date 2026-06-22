<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * desa_admin hanya mengelola modul desanya (cek per-record `desa_id`, mandiri —
 * tak bergantung pada scoping query saja). Modul global (desa_id null) = super_admin.
 */
class ModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function view(User $user, Module $module): bool
    {
        return $this->managesInDesa($user, $module);
    }

    public function create(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function update(User $user, Module $module): bool
    {
        return $this->managesInDesa($user, $module);
    }

    public function delete(User $user, Module $module): bool
    {
        return $this->managesInDesa($user, $module);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restore(User $user, Module $module): bool
    {
        return $this->managesInDesa($user, $module);
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return $this->managesInDesa($user, $module);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function replicate(User $user, Module $module): bool
    {
        return $this->managesInDesa($user, $module);
    }

    public function reorder(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    /** desa_admin hanya modul desanya sendiri (modul global dikecualikan). */
    private function managesInDesa(User $user, Module $module): bool
    {
        return $user->isDesaAdmin()
            && $user->desa_id !== null
            && $module->desa_id === $user->desa_id;
    }
}
