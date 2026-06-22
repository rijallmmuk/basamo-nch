<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi wilayah administratif resmi (Kepmendagri) + geometri batasnya.
 *
 * - `ref_wilayah`   : tabel datar referensi (prov/kab/kec/desa), hierarki via parent_kode.
 * - `wilayah_boundaries` : geometri batas (sumber tunggal peta), dipisah agar tabel
 *   referensi tetap ringan tanpa blob geometri ~MB.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Level diturunkan dari format kode (1=prov, 2=kab/kota, 3=kecamatan, 4=desa/kelurahan).
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

            $table->index('level');
            $table->index('parent_kode');
        });

        // Geometri batas semua level. `geom` MULTIPOLYGON penuh (SRID 0 kartesian) untuk
        // ST_Contains/point-in-polygon; `geom_simplified` (Douglas-Peucker) untuk render ringan.
        // MariaDB tak punya ST_Simplify, jadi simplifikasi dilakukan saat impor.
        Schema::create('wilayah_boundaries', function (Blueprint $table) {
            $table->string('kode', 13)->primary();          // join ke ref_wilayah.kode
            $table->unsignedTinyInteger('level');            // 1=prov..4=desa
            $table->string('parent_kode', 13)->nullable();
            $table->string('nama', 150);
            $table->double('lat')->nullable();               // titik tengah (label/marker)
            $table->double('lng')->nullable();

            $table->geometry('geom');                        // MULTIPOLYGON penuh
            $table->geometry('geom_simplified')->nullable(); // versi ringan untuk render

            $table->index('level');
            $table->index('parent_kode');
        });

        // Spatial index untuk ST_Contains dsb. — hanya MySQL/MariaDB (SQLite test tak dukung).
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('wilayah_boundaries', function (Blueprint $table) {
                $table->spatialIndex('geom');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah_boundaries');
        Schema::dropIfExists('ref_wilayah');
    }
};
