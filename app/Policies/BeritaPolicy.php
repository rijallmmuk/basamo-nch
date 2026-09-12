<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BeritaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOperator() || $user->isDpmd();
    }

    public function view(User $user, Berita $berita): bool
    {
        if ($user->isSuperAdmin() || $user->isDpmd()) {
            return true;
        }

        if ($user->isOperator()) {
            return $berita->semua_nagari
                || $berita->nagari_id === $user->nagari_id
                || $berita->nagaris()->where('nagaris.id', $user->nagari_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOperator();
    }

    public function update(User $user, Berita $berita): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isOperator()) {
            return $berita->nagari_id === $user->nagari_id;
        }

        return false;
    }

    public function delete(User $user, Berita $berita): bool
    {
        return $this->update($user, $berita);
    }

    public function restore(User $user, Berita $berita): bool
    {
        return $this->update($user, $berita);
    }

    public function forceDelete(User $user, Berita $berita): bool
    {
        return $this->update($user, $berita);
    }
}
