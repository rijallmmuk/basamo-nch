<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\UmkmProduct;
use App\Models\User;
use App\Support\NagariContext;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Otorisasi produk: pemilik mengelola produk usahanya sendiri; operator/superadmin
 * mengelola produk di nagarinya. superadmin dilewatkan via Gate::before.
 *
 * Produk tidak punya arsip, jadi tidak ada ability restore/forceDelete: `delete`
 * memang berarti hapus permanen.
 */
class UmkmProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOperator() || $user->usesUmkmSelfService() || $user->hasRole('dpmd');
    }

    public function view(User $user, UmkmProduct $product): bool
    {
        return $user->hasRole('dpmd') || $this->ownsProduct($user, $product) || $this->managesInNagari($user, $product);
    }

    /** Pemilik atau admin menambah produk pada lapak yang berada dalam cakupannya. */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isOperator()) {
            return $user->managedNagariId(NagariContext::UMKM_PRODUK) !== null;
        }

        return $user->usesUmkmSelfService() && $user->umkmProfile()->exists();
    }

    public function update(User $user, UmkmProduct $product): bool
    {
        return $user->isSuperAdmin() || $this->ownsProduct($user, $product) || $this->managesInNagari($user, $product);
    }

    /**
     * Hapus produk = PERMANEN, berikut seluruh fotonya. Pemilik menghapus produknya
     * sendiri; operator nagari juga boleh (moderasi). superadmin via Gate::before.
     */
    public function delete(User $user, UmkmProduct $product): bool
    {
        return $this->ownsProduct($user, $product) || $this->managesInNagari($user, $product);
    }

    /** Aksi massal (bulk) TIDAK dipakai di panel (klik baris → aksi per-record). */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function ownsProduct(User $user, UmkmProduct $product): bool
    {
        return $user->usesUmkmSelfService()
            && $product->umkmProfile?->user_id === $user->id;
    }

    /**
     * operator hanya produk UMKM di nagarinya sendiri (cek per-record `nagari_id`,
     * mandiri — tak bergantung pada scoping query resource saja, konsisten dgn
     * ModulePolicy/EvaluasiPolicy).
     */
    private function managesInNagari(User $user, UmkmProduct $product): bool
    {
        return $user->isOperator()
            && $user->nagari_id !== null
            && $product->umkmProfile?->nagari_id === $user->nagari_id;
    }
}
