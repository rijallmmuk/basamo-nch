<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nagaris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 50)->unique();
            $table->string('provinsi', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            // Sebutan unit wilayah khas nagari (Jorong/Dusun/Korong/Kampuang/…).
            $table->string('wilayah_label', 30)->default('Jorong');
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_lng', 11, 8)->nullable();
            $table->string('kontak', 20)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // FK users.nagari_id (users dibuat lebih dulu). Index FK dipenuhi komposit
        // users_nagari_role_status_xp_idx (prefix kiri).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('nagari_id')->references('id')->on('nagaris')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['nagari_id']);
        });

        Schema::dropIfExists('nagaris');
    }
};
