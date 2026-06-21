<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Desa;
use App\Models\User;

/**
 * Desa (akar multi-tenancy) hanya dikelola super_admin, yang dilewatkan via
 * Gate::before. Role lain ditolak penuh.
 */
class DesaPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Desa $desa): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Desa $desa): bool
    {
        return false;
    }

    public function delete(User $user, Desa $desa): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Desa $desa): bool
    {
        return false;
    }

    public function forceDelete(User $user, Desa $desa): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Desa $desa): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
