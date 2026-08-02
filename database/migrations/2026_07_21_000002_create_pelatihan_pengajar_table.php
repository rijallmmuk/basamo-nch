<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan pengajar per PELAKSANAAN pelatihan (banyak-ke-banyak). Pengajar HANYA
 * boleh mengelola konten pelaksanaan yang ia buat sendiri atau yang ditugaskan
 * kepadanya (PelatihanPolicy::kelolaKonten + cabang pengajar di Module/Evaluasi/
 * Discussion). Memakai TEMA yang sama TIDAK memberi akses apa pun ke pelaksanaan
 * milik pengajar lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelatihan_pengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelatihan_id')->constrained('pelatihans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pelatihan_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelatihan_pengajar');
    }
};
