<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas desa (tenant) + sub-unit wilayahnya.
 *
 * - `desas`    : desa/nagari tenant; tertaut ke wilayah resmi via `wilayah_kode`.
 *   Nama prov/kab/kec disimpan denormalized untuk display cepat.
 * - `desa_units` : sub-unit dalam desa (Jorong/Dusun/Korong/…); sebutannya diatur
 *   per desa via `desas.jenis_sub_unit_id`.
 *
 * Sekalian memasang FK `users.desa_id` & `users.desa_unit_id` (kolomnya dibuat di
 * migrasi users, FK ditunda ke sini karena desas/desa_units dibuat setelah users).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            // Tautan ke wilayah resmi level desa/kelurahan (Kepmendagri). Nullable.
            $table->string('wilayah_kode', 13)->nullable();
            // Penyebutan setingkat desa (Desa/Kelurahan/Nagari/…), dipilih super_admin.
            $table->foreignId('jenis_desa_id')->constrained('jenis_desa')->restrictOnDelete();
            $table->string('provinsi', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            // Sebutan sub-unit (Jorong/Dusun/Korong/…), diatur admin desa. Boleh kosong.
            $table->foreignId('jenis_sub_unit_id')->nullable()->constrained('jenis_sub_unit')->nullOnDelete();
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_lng', 11, 8)->nullable();
            $table->string('kontak', 20)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('wilayah_kode')->references('kode')->on('ref_wilayah')->nullOnDelete();
            // UNIQUE nullable: 1 desa resmi = 1 tenant (banyak NULL tetap diizinkan).
            // Sertakan deleted_at agar kode desa bisa dipakai ulang setelah desa diarsip;
            // keunikan antar baris AKTIF dijaga di aplikasi (DesaForm ->withoutTrashed()).
            // Prefix kiri wilayah_kode tetap memenuhi index FK ke ref_wilayah.
            $table->unique(['wilayah_kode', 'deleted_at']);
        });

        Schema::create('desa_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
            $table->softDeletes();

            $table->index('desa_id');
            // Sertakan deleted_at agar nama sub-unit bisa dipakai ulang setelah dihapus;
            // keunikan antar baris AKTIF dijaga di aplikasi (DesaUnitForm ->withoutTrashed()).
            $table->unique(['desa_id', 'nama', 'deleted_at']);
        });

        // FK users → desas/desa_units (kolom dibuat lebih dulu di migrasi users).
        // Index FK desa_id dipenuhi komposit users_desa_role_status_xp_idx (prefix kiri).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('desa_id')->references('id')->on('desas')->nullOnDelete();
            $table->foreign('desa_unit_id')->references('id')->on('desa_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['desa_unit_id']);
            $table->dropForeign(['desa_id']);
        });

        Schema::dropIfExists('desa_units');
        Schema::dropIfExists('desas');
    }
};
