<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UmkmCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Taksonomi UMKM bersifat global → hanya superadmin (via Gate::before) yang
 * boleh mengelola. operator tidak. Semua metode false untuk non-superadmin.
 */
class UmkmCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, UmkmCategory $category): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, UmkmCategory $category): bool
    {
        return false;
    }

    public function delete(User $user, UmkmCategory $category): bool
    {
        return false;
    }
}
