<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UmkmCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Taksonomi UMKM bersifat global → hanya super_admin (via Gate::before) yang
 * boleh mengelola. nagari_admin tidak. Semua metode false untuk non-super_admin.
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
