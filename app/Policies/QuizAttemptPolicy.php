<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before. nagari_admin boleh melihat (untuk pemantauan).
 * Attempt dibuat oleh warga lewat portal (di luar policy ini), bukan oleh admin.
 */
class QuizAttemptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isNagariAdmin();
    }

    public function view(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->isNagariAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    public function delete(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    public function forceDelete(User $user, QuizAttempt $quizAttempt): bool
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

    public function replicate(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
