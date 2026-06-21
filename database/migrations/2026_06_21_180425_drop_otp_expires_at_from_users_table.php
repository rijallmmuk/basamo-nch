<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OTP awal kini tanpa kedaluwarsa (tersimpan & terlihat hingga sandi diganti,
     * lalu dihapus). Kolom `otp_expires_at` tak lagi dipakai.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('otp_expires_at')->nullable()->after('initial_otp');
        });
    }
};
