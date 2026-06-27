<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // FK → desas/desa_units ditambah di migrasi tabel terkait (dibuat setelah users).
            $table->unsignedBigInteger('desa_id')->nullable();      // nullable untuk super_admin
            $table->unsignedBigInteger('desa_unit_id')->nullable(); // sub-unit wilayah warga (opsional)
            $table->string('name');
            // username (kode nagari admin desa) nullable; unique disertai deleted_at di
            // bawah agar kode bisa dipakai ulang setelah desa/admin diarsip.
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();         // opsional: NIK identitas utama
            $table->string('phone', 20)->nullable();               // No. WhatsApp/HP
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            // OTP awal tanpa kedaluwarsa: tersimpan & terlihat hingga sandi diganti, lalu dihapus.
            $table->string('initial_otp', 12)->nullable();         // OTP awal (kredensial sementara)
            $table->string('role', 20)->nullable();                // persona: super_admin|desa_admin|warga
            $table->timestamp('umkm_access_granted_at')->nullable(); // kapabilitas UMKM (bukan role)
            $table->unsignedInteger('total_xp')->default(0);
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role');
            // Leaderboard: WHERE desa,role,status ORDER BY total_xp (filter + sort 1 index).
            // Komposit ini juga jadi index FK untuk desa_id (prefix kiri).
            $table->index(['desa_id', 'role', 'status', 'total_xp'], 'users_desa_role_status_xp_idx');
            $table->unique(['username', 'deleted_at']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
