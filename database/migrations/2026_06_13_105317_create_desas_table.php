<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            // Penyebutan wilayah administratif setingkat desa (Desa/Kelurahan/Desa/…),
            // dipilih super_admin & melekat ke entitas. Skala nasional.
            $table->string('jenis', 30);
            $table->string('kode', 50)->unique();
            $table->string('provinsi', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            // Sebutan sub-unit (Jorong/Dusun/Korong/…), diatur admin desa. Boleh kosong.
            $table->string('wilayah_label', 30)->nullable();
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_lng', 11, 8)->nullable();
            $table->string('kontak', 20)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // FK users.desa_id (users dibuat lebih dulu). Index FK dipenuhi komposit
        // users_desa_role_status_xp_idx (prefix kiri).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('desa_id')->references('id')->on('desas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['desa_id']);
        });

        Schema::dropIfExists('desas');
    }
};
