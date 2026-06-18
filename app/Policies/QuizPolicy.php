<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * Policy ini mengatur nagari_admin; scope per-nagari ditegakkan di query Resource.
 */
class QuizPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->isNagariAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isNagariAdmin();
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->isNagariAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return $user->isNagariAdmin();
    }

    public function forceDelete(User $user, Quiz $quiz): bool
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

    public function replicate(User $user, Quiz $quiz): bool
    {
        return $user->isNagariAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isNagariAdmin();
    }
}
