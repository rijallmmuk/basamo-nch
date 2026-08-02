<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pekerjaan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Data master global → hanya superadmin (via Gate::before) yang boleh
 * mengelola. Semua metode false untuk peran lain.
 */
class PekerjaanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Pekerjaan $pekerjaan): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Pekerjaan $pekerjaan): bool
    {
        return false;
    }

    public function delete(User $user, Pekerjaan $pekerjaan): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
