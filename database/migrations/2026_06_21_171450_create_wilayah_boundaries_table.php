<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Geometri batas wilayah (semua level: prov/kab/kec/desa), sumber tunggal peta.
     * Dipisah dari `ref_wilayah` agar tabel referensi tetap ringan (tanpa blob ~MB).
     * Disimpan sebagai tipe spasial: `geom` (akurat, untuk ST_Contains/point-in-polygon)
     * + `geom_simplified` (Douglas-Peucker, untuk render peta yang ringan).
     * MariaDB tak punya ST_Simplify, jadi simplifikasi dilakukan saat impor.
     */
    public function up(): void
    {
        Schema::create('wilayah_boundaries', function (Blueprint $table) {
            $table->string('kode', 13)->primary();          // join ke ref_wilayah.kode
            $table->unsignedTinyInteger('level');            // 1=prov..4=desa
            $table->string('parent_kode', 13)->nullable();
            $table->string('nama', 150);
            $table->double('lat')->nullable();               // titik tengah (label/marker)
            $table->double('lng')->nullable();

            $table->geometry('geom');                        // MULTIPOLYGON penuh (SRID 4326 saat insert)
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
