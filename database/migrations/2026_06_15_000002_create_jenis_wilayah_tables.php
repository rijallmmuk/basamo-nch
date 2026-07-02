<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi global (lintas-desa, dikelola super_admin) penyebutan wilayah:
 * - `jenis_desa`     : sebutan setingkat desa (Desa/Kelurahan/Nagari/Gampong/…).
 * - `jenis_sub_unit` : sebutan sub-unit di bawah desa (Jorong/Dusun/Lingkungan/RW/…).
 *
 * Enum referensi kecil & fixed → di-seed langsung di migrasi (sama seperti
 * `umkm_categories`), agar selalu tersedia tanpa perlu menjalankan seeder.
 */
return new class extends Migration
{
    private const JENIS_DESA = [
        'Desa', 'Kelurahan', 'Nagari', 'Gampong', 'Kampung', 'Kalurahan',
        'Lembang', 'Pekon', 'Tiyuh', 'Negeri', 'Nagori', 'Huta',
    ];

    private const JENIS_SUB_UNIT = [
        'Dusun', 'Lingkungan', 'Jorong', 'Korong', 'Dukuh', 'Padukuhan',
        'Banjar', 'Kampung', 'Lorong', 'RW',
    ];

    public function up(): void
    {
        Schema::create('jenis_desa', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('jenis_sub_unit', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $this->seed('jenis_desa', self::JENIS_DESA);
        $this->seed('jenis_sub_unit', self::JENIS_SUB_UNIT);
    }

    /** @param  array<int, string>  $names */
    private function seed(string $table, array $names): void
    {
        $now = now();
        $rows = [];

        // Urutan 1-based — konsisten dgn seed agama/status_perkawinan/pekerjaan.
        foreach (array_values($names) as $i => $nama) {
            $rows[] = ['nama' => $nama, 'urutan' => $i + 1, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table($table)->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_sub_unit');
        Schema::dropIfExists('jenis_desa');
    }
};
