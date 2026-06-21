<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * Policy ini mengatur desa_admin; scope per-desa ditegakkan di query Resource.
 */
class QuizPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin();
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin();
    }

    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function replicate(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isDesaAdmin();
    }
}
