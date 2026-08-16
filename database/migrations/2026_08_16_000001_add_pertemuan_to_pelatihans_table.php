<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pertemuan daring pada pelatihan, untuk webinar.
 *
 * Sebuah pelatihan disebut webinar cukup dari terisinya `pertemuan_url`. Tidak ada
 * kolom jenis tersendiri supaya tidak lahir keadaan mustahil semacam "jenis webinar
 * tetapi tautannya kosong".
 *
 * Waktunya MURNI keterangan: ditampilkan dan dapat diurutkan, tetapi tidak membuka
 * maupun mengunci apa pun. Gerbang akses tetap hanya tombol Kunci/Buka.
 *
 * Seluruh kolom nullable, jadi pelatihan yang sudah berjalan di produksi tidak
 * tersentuh dan tidak ada isian yang perlu diisi ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelatihans', function (Blueprint $table) {
            $table->string('pertemuan_url', 2048)->nullable()->after('deskripsi');
            $table->string('pertemuan_platform', 32)->nullable()->after('pertemuan_url');
            $table->dateTime('pertemuan_mulai')->nullable()->after('pertemuan_platform');
            $table->dateTime('pertemuan_selesai')->nullable()->after('pertemuan_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('pelatihans', function (Blueprint $table) {
            $table->dropColumn([
                'pertemuan_url',
                'pertemuan_platform',
                'pertemuan_mulai',
                'pertemuan_selesai',
            ]);
        });
    }
};
