<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi global status perkawinan. ID diselaraskan PERSIS dengan
 * `tweb_penduduk_kawin` OpenSID (cocok 1:1, tak ada penyesuaian).
 */
return new class extends Migration
{
    private const STATUS_PERKAWINAN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];

    public function up(): void
    {
        Schema::create('status_perkawinan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = [];
        foreach (self::STATUS_PERKAWINAN as $nama) {
            $rows[] = ['nama' => $nama, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('status_perkawinan')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('status_perkawinan');
    }
};
