<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi wilayah administratif resmi (Kepmendagri): prov/kab/kec/nagari,
 * hierarki via `parent_kode`. Level diturunkan dari format kode (1=prov,
 * 2=kab/kota, 3=kecamatan, 4=nagari/kelurahan). Kolom geo (lat/lng/luas/
 * penduduk/ibukota) hanya terisi untuk prov & kab/kota (level 1–2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_wilayah', function (Blueprint $table) {
            $table->string('kode', 13)->primary();
            $table->string('nama', 100);
            $table->unsignedTinyInteger('level');                 // 1..4
            $table->string('parent_kode', 13)->nullable();

            $table->string('ibukota', 100)->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->double('luas')->nullable();                   // km²
            $table->unsignedBigInteger('penduduk')->nullable();
            $table->string('kode_bps', 10)->nullable();           // crosswalk BPS (beda dari Kemendagri), utk API eksternal (mis. SDGs Kemendesa)
            $table->string('kodepos', 5)->nullable();

            $table->index('level');
            $table->index('parent_kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_wilayah');
    }
};
