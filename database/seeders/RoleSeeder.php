<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lima peran inti platform (role custom buatan superadmin tidak dihapus).
 * Multi-role Spatie = sumber kebenaran akses (kolom users.role sudah
 * dipensiunkan sejak 2026-07-21). Idempotent:
 * aman dijalankan berulang. Satu akun boleh memegang lebih dari satu peran
 * (mis. warga + umkm); peran ditegakkan Gate/Policy, bukan tabel user terpisah.
 */
class RoleSeeder extends Seeder
{
    /** @var list<string> */
    public const ROLES = ['superadmin', 'operator', 'warga', 'pengajar', 'dpmd'];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
