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
        Schema::table('kontak_masuks', function (Blueprint $table) {
            $table->dropColumn('file_pendukung');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontak_masuks', function (Blueprint $table) {
            $table->string('file_pendukung')->nullable()->after('isi');
        });
    }
};
