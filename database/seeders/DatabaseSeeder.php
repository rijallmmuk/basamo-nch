<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // DemoSeeder memanggil CoreSeeder (role + super admin) lebih dulu, lalu
        // mengisi data demo. Produksi: `db:seed --class=CoreSeeder` saja.
        $this->call(DemoSeeder::class);
    }
}
