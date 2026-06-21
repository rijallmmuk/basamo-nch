<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referensi wilayah administratif resmi (Kepmendagri). Datar: level diturunkan
        // dari format kode (1=prov, 2=kab/kota, 3=kecamatan, 4=desa/kelurahan).
        // Kolom geo hanya terisi untuk prov & kab/kota (level 1–2).
        Schema::create('ref_wilayah', function (Blueprint $table) {
            $table->string('kode', 13)->primary();
            $table->string('nama', 100);
            $table->unsignedTinyInteger('level');                 // 1..4
            $table->string('parent_kode', 13)->nullable();

            $table->string('ibukota', 100)->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->float('elv')->nullable();                     // elevasi (m)
            $table->tinyInteger('tz')->nullable();                // zona waktu (jam)
            $table->double('luas')->nullable();                   // km²
            $table->unsignedBigInteger('penduduk')->nullable();
            $table->longText('path')->nullable();                 // polygon batas (peta)

            $table->index('level');
            $table->index('parent_kode');
        });

        // Tautan desa tenant → wilayah resmi (level desa/kelurahan). Nullable; nama
        // provinsi/kabupaten/kecamatan tetap disimpan denormalized di `desas` utk display.
        Schema::table('desas', function (Blueprint $table) {
            $table->string('wilayah_kode', 13)->nullable()->after('kode');
            $table->foreign('wilayah_kode')->references('kode')->on('ref_wilayah')->nullOnDelete();
            $table->index('wilayah_kode');
        });
    }

    public function down(): void
    {
        Schema::table('desas', function (Blueprint $table) {
            $table->dropForeign(['wilayah_kode']);
            $table->dropColumn('wilayah_kode');
        });

        Schema::dropIfExists('ref_wilayah');
    }
};
