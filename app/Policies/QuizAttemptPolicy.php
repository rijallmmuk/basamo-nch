<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\QuizAttempt;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizAttemptPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QuizAttempt');
    }

    public function view(AuthUser $authUser, QuizAttempt $quizAttempt): bool
    {
        return $authUser->can('View:QuizAttempt');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QuizAttempt');
    }

    public function update(AuthUser $authUser, QuizAttempt $quizAttempt): bool
    {
        return $authUser->can('Update:QuizAttempt');
    }

    public function delete(AuthUser $authUser, QuizAttempt $quizAttempt): bool
    {
        return $authUser->can('Delete:QuizAttempt');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QuizAttempt');
    }

    public function restore(AuthUser $authUser, QuizAttempt $quizAttempt): bool
    {
        return $authUser->can('Restore:QuizAttempt');
    }

    public function forceDelete(AuthUser $authUser, QuizAttempt $quizAttempt): bool
    {
        return $authUser->can('ForceDelete:QuizAttempt');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QuizAttempt');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QuizAttempt');
    }

    public function replicate(AuthUser $authUser, QuizAttempt $quizAttempt): bool
    {
        return $authUser->can('Replicate:QuizAttempt');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QuizAttempt');
    }

}