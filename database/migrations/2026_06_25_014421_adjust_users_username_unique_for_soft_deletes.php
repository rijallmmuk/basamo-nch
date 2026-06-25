<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Username admin desa kini OTOMATIS dari kode nagari (lihat DesaResource::syncAdmin),
 * dan kode nagari boleh dipakai ulang setelah desa diarsipkan. Agar username admin
 * bekas (yang sudah ter-soft-delete) tak memblokir admin baru berkode sama, sertakan
 * deleted_at pada unique. Konsisten dgn desas.wilayah_kode & desa_units. Keunikan
 * antar username AKTIF tetap terjaga karena username diturunkan dari kode nagari yang
 * sendirinya unik antar desa aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_username_unique');
            $table->unique(['username', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username', 'deleted_at']);
            $table->unique('username');
        });
    }
};
