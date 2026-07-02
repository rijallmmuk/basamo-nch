<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JenisDesa;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Data master global → hanya super_admin (via Gate::before) yang boleh
 * mengelola. Semua metode false untuk peran lain.
 */
class JenisDesaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, JenisDesa $jenisDesa): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, JenisDesa $jenisDesa): bool
    {
        return false;
    }

    public function delete(User $user, JenisDesa $jenisDesa): bool
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
