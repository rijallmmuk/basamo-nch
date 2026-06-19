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
        // Sebutan unit wilayah khas nagari ini (Jorong/Dusun/Korong/Kampuang/…).
        Schema::table('nagaris', function (Blueprint $table) {
            $table->string('wilayah_label', 30)->default('Jorong')->after('kecamatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nagaris', function (Blueprint $table) {
            $table->dropColumn('wilayah_label');
        });
    }
};
