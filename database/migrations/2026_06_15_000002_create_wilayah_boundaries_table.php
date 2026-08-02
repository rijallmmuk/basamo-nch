<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geometri batas wilayah semua level (sumber tunggal peta), dipisah dari
 * `ref_wilayah` agar tabel referensi tetap ringan tanpa blob geometri ~MB.
 * `geom` MULTIPOLYGON penuh (SRID 0 kartesian) untuk ST_Contains/point-in-
 * polygon; `geom_simplified` (Douglas-Peucker) untuk render ringan. MariaDB
 * tak punya ST_Simplify, jadi simplifikasi dilakukan saat impor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah_boundaries', function (Blueprint $table) {
            $table->string('kode', 13)->primary();          // join ke ref_wilayah.kode
            $table->unsignedTinyInteger('level');            // 1=prov..4=nagari
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
    }
};
