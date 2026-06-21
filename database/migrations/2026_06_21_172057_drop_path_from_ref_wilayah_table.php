<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Geometri batas pindah ke tabel spasial `wilayah_boundaries` (sumber tunggal).
     * Kolom `path` di `ref_wilayah` tak lagi dipakai.
     */
    public function up(): void
    {
        Schema::table('ref_wilayah', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }

    public function down(): void
    {
        Schema::table('ref_wilayah', function (Blueprint $table) {
            $table->longText('path')->nullable();
        });
    }
};
