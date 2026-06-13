<?php

namespace Database\Seeders;

use App\Models\Nagari;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan semua role tersedia
        foreach (['super_admin', 'nagari_admin', 'warga', 'umkm_owner'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $nagari = Nagari::where('kode', 'NCH-001')->first();

        // Super Admin (sudah dibuat via shield:super-admin, pastikan role & status set)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@basamo.nch'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Admin Nagari
        $nagariAdmin = User::firstOrCreate(
            ['email' => 'admin.nagari@basamo.nch'],
            [
                'name' => 'Admin Nagari Harapan',
                'password' => Hash::make('password'),
                'nagari_id' => $nagari?->id,
                'role' => 'nagari_admin',
                'status' => 'active',
            ]
        );
        $nagariAdmin->assignRole('nagari_admin');
    }
}
