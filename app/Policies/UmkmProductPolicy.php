<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UmkmProduct;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Otorisasi produk di portal: pemilik hanya boleh mengelola produk usahanya
 * sendiri. desa_admin mengelola verifikasi via Resource (bukan policy ini).
 * super_admin dilewatkan via Gate::before.
 */
class UmkmProductPolicy
{
    use HandlesAuthorization;

    /** Antrian verifikasi admin: desa_admin (super_admin via Gate::before). */
    public function viewAny(User $user): bool
    {
        return $user->isDesaAdmin();
    }

    public function view(User $user, UmkmProduct $product): bool
    {
        return $user->isDesaAdmin();
    }

    public function update(User $user, UmkmProduct $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function delete(User $user, UmkmProduct $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * Pulihkan/hapus permanen produk terhapus = wewenang admin desa (semua aksi
     * reversible — warga yang keliru menghapus minta admin memulihkan).
     * Scoping desa dijaga query resource; super_admin via Gate::before.
     */
    public function restore(User $user, UmkmProduct $product): bool
    {
        return $user->isDesaAdmin();
    }

    public function forceDelete(User $user, UmkmProduct $product): bool
    {
        return $user->isDesaAdmin();
    }

    private function ownsProduct(User $user, UmkmProduct $product): bool
    {
        return $user->hasUmkmAccess()
            && $product->umkmProfile?->user_id === $user->id;
    }
}
