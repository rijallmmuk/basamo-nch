<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * Policy ini mengatur nagari_admin; scope per-nagari ditegakkan di query Resource.
 */
class WilayahPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function view(User $user, Wilayah $wilayah): bool
    {
        return $user->isNagariAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function update(User $user, Wilayah $wilayah): bool
    {
        return $user->isNagariAdmin();
    }

    public function delete(User $user, Wilayah $wilayah): bool
    {
        return $user->isNagariAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function restore(User $user, Wilayah $wilayah): bool
    {
        return $user->isNagariAdmin();
    }

    public function forceDelete(User $user, Wilayah $wilayah): bool
    {
        return $user->isNagariAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isNagariAdmin();
    }
}
