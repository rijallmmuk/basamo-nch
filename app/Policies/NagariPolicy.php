<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Nagari;
use App\Models\User;

/**
 * Nagari = ranah SUPERADMIN (penuh) & DPMD (read-only) saja — keduanya lewat Gate::before.
 * Operator diberikan akses view dan update secara spesifik HANYA untuk nagarinya sendiri
 * (misalnya untuk mengedit sampul).
 */
class NagariPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Nagari $nagari): bool
    {
        if ($user->isOperator()) {
            return $user->nagari_id === $nagari->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Nagari $nagari): bool
    {
        if ($user->isOperator()) {
            return $user->nagari_id === $nagari->id;
        }

        return false;
    }

    public function delete(User $user, Nagari $nagari): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Nagari $nagari): bool
    {
        return false;
    }

    public function forceDelete(User $user, Nagari $nagari): bool
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

    public function replicate(User $user, Nagari $nagari): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
