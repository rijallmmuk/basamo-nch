<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');          // No. WhatsApp/HP warga
            $table->boolean('must_change_password')->default(false)->after('password'); // paksa ganti sandi
            $table->string('initial_otp', 12)->nullable()->after('must_change_password'); // OTP awal (sementara)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'must_change_password', 'initial_otp']);
        });
    }
};
