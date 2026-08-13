<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sertifikat yang sudah diterbitkan atas nama seorang warga.
 *
 * Baris ditulis sekali saat warga pertama kali mengambil sertifikatnya, lalu tidak
 * berubah lagi: nomor seri dan tanggal terbit harus tetap sama setiap kali berkasnya
 * diunduh ulang, kalau tidak verifikasi publik akan menyangkal sertifikat yang sah.
 *
 * Mode `unggah` juga dicatat di sini walau berkasnya sama untuk semua peserta,
 * supaya penyelenggara tetap tahu siapa yang sudah mengambilnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelatihan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_seri', 32)->unique();
            $table->timestamp('diterbitkan_pada');
            $table->timestamps();

            $table->unique(['pelatihan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
