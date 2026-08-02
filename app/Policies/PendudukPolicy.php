<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Penduduk;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/** Operator hanya mengelola identitas warga nagarinya; superadmin/DPMD via Gate. */
class PendudukPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): bool
    {
        return $actor->isOperator();
    }

    public function view(User $actor, Penduduk $penduduk): bool
    {
        return $this->managesInNagari($actor, $penduduk);
    }

    public function create(User $actor): bool
    {
        return $actor->isOperator();
    }

    public function update(User $actor, Penduduk $penduduk): bool
    {
        return $this->managesInNagari($actor, $penduduk);
    }

    public function delete(User $actor, Penduduk $penduduk): bool
    {
        return $this->managesInNagari($actor, $penduduk);
    }

    public function restore(User $actor, Penduduk $penduduk): bool
    {
        return $this->managesInNagari($actor, $penduduk);
    }

    public function forceDelete(User $actor, Penduduk $penduduk): bool
    {
        return $this->managesInNagari($actor, $penduduk);
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    private function managesInNagari(User $actor, Penduduk $penduduk): bool
    {
        return $actor->isOperator()
            && $actor->nagari_id !== null
            && $penduduk->nagari_id === $actor->nagari_id;
    }
}
