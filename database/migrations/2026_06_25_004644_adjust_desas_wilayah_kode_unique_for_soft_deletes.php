<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Desa memakai SoftDeletes, tapi unique `wilayah_kode` tak menyertakan deleted_at →
 * kode desa yang sudah diarsipkan tak bisa dipakai ulang. Sertakan deleted_at agar
 * desa yang sama bisa dibuat lagi setelah dihapus. Keunikan antar baris AKTIF tetap
 * dijaga di sisi aplikasi (DesaForm: unique ->withoutTrashed()). Konsisten dgn
 * perlakuan desa_units (lihat adjust_desa_units_unique_for_soft_deletes).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tambah unique komposit DULU agar tetap ada index berawalan `wilayah_kode`
        // untuk FK desas_wilayah_kode_foreign, baru drop unique lama (kalau tidak,
        // MySQL menolak: index masih dipakai foreign key).
        Schema::table('desas', function (Blueprint $table): void {
            $table->unique(['wilayah_kode', 'deleted_at']);
            $table->dropUnique('desas_wilayah_kode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('desas', function (Blueprint $table): void {
            $table->unique('wilayah_kode');
            $table->dropUnique(['wilayah_kode', 'deleted_at']);
        });
    }
};
