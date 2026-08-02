<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Esensial produksi: role RBAC + akun super admin. Idempotent — aman dijalankan
 * berulang. Produksi cukup jalankan seeder ini: `db:seed --class=CoreSeeder --force`.
 */
class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Data master kependudukan (agama, pendidikan, status perkawinan, pekerjaan).
        // Dijalankan LEBIH DULU dari apa pun yang menyentuh Penduduk, karena kolom
        // penduduk merujuk id di tabel-tabel ini.
        $this->call(DataMasterSeeder::class);

        // Referensi wilayah administratif resmi (Sumbar dulu) + geometri batas peta.
        $this->call(WilayahSumbarSeeder::class);
        $this->call(WilayahBoundarySeeder::class);

        // Referensi SDGs Desa (4 pilar, 18 poin, sasaran, indikator).
        $this->call(SdgReferenceSeeder::class);

        // Perangkat EWS banjir bandang (Pilar 4). Melewati nagari yang belum ada,
        // jadi aman dijalankan sebelum nagarinya dibuat lewat menu Nagari.
        $this->call(EwsDeviceSeeder::class);

        // Sandi super admin: di PRODUKSI dibangkitkan acak (dicetak SEKALI di console —
        // catat segera) + wajib diganti saat login pertama. Jangan pernah sandi
        // hardcoded di server nyata. Lokal/demo tetap 'password' agar mudah.
        $password = app()->isProduction() ? Str::random(24) : 'password';

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@basamo.nch'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make($password),
                'must_change_password' => app()->isProduction(),
                'status' => 'active',
            ],
        );

        $superAdmin->assignRole('superadmin');

        if (app()->isProduction() && $superAdmin->wasRecentlyCreated) {
            $this->command?->warn("Sandi awal super admin (username: superadmin): {$password}");
            $this->command?->warn('Catat sekarang — tidak ditampilkan lagi. Wajib diganti saat login pertama.');
        }
    }
}
