<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi global agama (lintas-nagari, dikelola superadmin). ID diselaraskan
 * PERSIS dengan `tweb_penduduk_agama` OpenSID (7 agama, termasuk "Kepercayaan
 * Terhadap Tuhan YME/Lainnya") — sistem milik nagari yang jadi sumber file
 * import/export penduduk — supaya ID di file mereka dipakai langsung tanpa
 * translasi. Himpunan baku & jarang berubah → di-seed langsung di migrasi.
 */
return new class extends Migration
{
    private const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan Terhadap Tuhan YME/Lainnya'];

    public function up(): void
    {
        Schema::create('agama', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = [];
        foreach (self::AGAMA as $nama) {
            $rows[] = ['nama' => $nama, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('agama')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('agama');
    }
};
