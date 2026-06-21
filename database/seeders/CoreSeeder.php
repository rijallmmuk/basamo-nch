<?php

namespace Database\Seeders;

use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
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

        // Referensi penyebutan administratif nasional (setingkat desa + sub-unit).
        $jenisDesa = [
            'Desa', 'Kelurahan', 'Nagari', 'Gampong', 'Kampung', 'Kalurahan',
            'Lembang', 'Pekon', 'Tiyuh', 'Negeri', 'Nagori', 'Huta',
        ];
        foreach ($jenisDesa as $urutan => $nama) {
            JenisDesa::firstOrCreate(['nama' => $nama], ['urutan' => $urutan]);
        }

        $jenisSubUnit = [
            'Dusun', 'Lingkungan', 'Jorong', 'Korong', 'Dukuh', 'Padukuhan',
            'Banjar', 'Kampung', 'Lorong', 'RW',
        ];
        foreach ($jenisSubUnit as $urutan => $nama) {
            JenisSubUnit::firstOrCreate(['nama' => $nama], ['urutan' => $urutan]);
        }

        // Referensi wilayah administratif resmi (Sumbar dulu).
        $this->call(WilayahSumbarSeeder::class);

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
