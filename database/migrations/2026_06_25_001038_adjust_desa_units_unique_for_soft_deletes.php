<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sub-unit memakai SoftDeletes, tapi unique (desa_id, nama) tak menyertakan
 * deleted_at → nama yang sudah dihapus tak bisa dipakai ulang. Sertakan deleted_at
 * agar nama bebas dipakai lagi setelah sub-unit dihapus. Keunikan antar baris AKTIF
 * tetap dijaga di sisi aplikasi (DesaUnitForm: unique ->withoutTrashed()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desa_units', function (Blueprint $table): void {
            $table->dropUnique('desa_units_desa_id_nama_unique');
            $table->unique(['desa_id', 'nama', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('desa_units', function (Blueprint $table): void {
            $table->dropUnique(['desa_id', 'nama', 'deleted_at']);
            $table->unique(['desa_id', 'nama']);
        });
    }
};
