<?php

namespace Database\Seeders;

use App\Models\Nagari;
use Illuminate\Database\Seeder;

class NagariSeeder extends Seeder
{
    public function run(): void
    {
        Nagari::firstOrCreate(
            ['kode' => 'NCH-001'],
            [
                'nama' => 'Nagari Contoh Harapan',
                'provinsi' => 'Sumatera Barat',
                'kabupaten' => 'Kabupaten Contoh',
                'kecamatan' => 'Kecamatan Harapan',
                'kontak' => '08123456789',
                'status' => 'active',
            ]
        );
    }
}
