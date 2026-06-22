<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * super_admin dilewatkan via Gate::before (akses penuh).
 * desa_admin hanya mengelola kuis modul desanya (cek per-record via modul induk,
 * mandiri — tak bergantung pada scoping query saja).
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
        return $this->managesInDesa($user, $quiz);
    }

    public function create(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->managesInDesa($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->managesInDesa($user, $quiz);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return $this->managesInDesa($user, $quiz);
    }

    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return $this->managesInDesa($user, $quiz);
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
        return $this->managesInDesa($user, $quiz);
    }

    public function reorder(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    /** desa_admin hanya kuis modul desanya (modul global dikecualikan). */
    private function managesInDesa(User $user, Quiz $quiz): bool
    {
        return $user->isDesaAdmin()
            && $user->desa_id !== null
            && $quiz->module?->desa_id === $user->desa_id;
    }
}
