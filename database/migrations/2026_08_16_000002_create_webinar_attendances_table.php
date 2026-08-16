<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan kehadiran warga pada pertemuan daring sebuah pelatihan.
 *
 * Pelatihan yang diisi lewat pertemuan daring boleh tidak berisi modul, sehingga tidak
 * ada progres yang bisa dipakai sebagai bukti mengikuti. Baris di sinilah yang
 * menggantikannya, dan menjadi satu-satunya syarat terbitnya sertifikat.
 *
 * Namanya diperbaiki menjadi `pelatihan_attendances` pada migrasi berikutnya.
 *
 * Unique `(pelatihan_id, user_id)` menjaga satu warga tercatat sekali saja, walau
 * tombolnya ditekan berulang atau dua permintaan datang berbarengan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinar_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelatihan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('hadir_pada');
            $table->timestamps();

            $table->unique(['pelatihan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinar_attendances');
    }
};
