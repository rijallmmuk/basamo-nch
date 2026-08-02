<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi = halaman isi sebuah modul, sekaligus unit penyelesaian warga. Isinya
 * array blok bertipe (teks/video/pdf/gambar/audio/lampiran) pada kolom JSON
 * `blocks` — lihat App\Enums\ModuleBlockType.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->json('blocks')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('module_id');
            $table->index('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materis');
    }
};
