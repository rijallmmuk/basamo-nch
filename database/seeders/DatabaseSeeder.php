<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya data esensial: role RBAC + super admin. Idempotent, aman diulang.
        $this->call(CoreSeeder::class);
    }
}
