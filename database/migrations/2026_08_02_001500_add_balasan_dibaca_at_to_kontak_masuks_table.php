<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontak_masuks', function (Blueprint $table) {
            $table->timestamp('balasan_dibaca_at')->nullable()->after('balasan');
        });
    }

    public function down(): void
    {
        Schema::table('kontak_masuks', function (Blueprint $table) {
            $table->dropColumn('balasan_dibaca_at');
        });
    }
};
