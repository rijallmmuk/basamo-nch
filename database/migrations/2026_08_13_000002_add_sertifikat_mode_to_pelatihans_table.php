<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asal sertifikat tiap pelatihan. Berkas untuk mode `unggah` disimpan media library
 * pada koleksi `sertifikat`, jadi tidak ada kolom berkas di sini.
 *
 * Pelatihan yang sudah berjalan di produksi jatuh ke `tidak`, keadaan yang sama
 * dengan sebelum kolom ini ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelatihans', function (Blueprint $table) {
            $table->string('sertifikat_mode', 20)->default('tidak')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pelatihans', function (Blueprint $table) {
            $table->dropColumn('sertifikat_mode');
        });
    }
};
