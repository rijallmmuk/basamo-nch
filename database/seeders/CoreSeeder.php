<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Esensial produksi: role RBAC + akun super admin. Idempotent — aman dijalankan
 * berulang. Produksi cukup jalankan seeder ini: `db:seed --class=CoreSeeder`.
 */
class CoreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'desa_admin', 'warga'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Penyebutan administratif (jenis_desa & jenis_sub_unit) di-seed di migrasi.

        // Referensi wilayah administratif resmi (Sumbar dulu) + geometri batas peta.
        $this->call(WilayahSumbarSeeder::class);
        $this->call(WilayahBoundarySeeder::class);

        User::firstOrCreate(
            ['email' => 'admin@basamo.nch'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ],
        )->assignRole('super_admin');
    }
}
