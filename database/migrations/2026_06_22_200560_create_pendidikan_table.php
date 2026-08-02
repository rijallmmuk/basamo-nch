<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi global jenjang pendidikan terakhir (SELESAI ditempuh, bukan
 * sedang ditempuh). ID diselaraskan PERSIS dengan `tweb_penduduk_pendidikan_kk`
 * OpenSID (10 jenjang).
 */
return new class extends Migration
{
    private const PENDIDIKAN = [
        'Tidak/Belum Sekolah', 'Belum Tamat SD/Sederajat', 'Tamat SD/Sederajat', 'SLTP/Sederajat',
        'SLTA/Sederajat', 'Diploma I/II', 'Akademi/Diploma III/S. Muda', 'Diploma IV/Strata I',
        'Strata II', 'Strata III',
    ];

    public function up(): void
    {
        Schema::create('pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = [];
        foreach (self::PENDIDIKAN as $nama) {
            $rows[] = ['nama' => $nama, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('pendidikan')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('pendidikan');
    }
};
