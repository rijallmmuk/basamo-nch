<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Soal pilihan ganda sebuah evaluasi (semua soal setara, tanpa bobot). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_pertanyaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluasi_id')->constrained('evaluasis')->cascadeOnDelete();
            $table->text('pertanyaan');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('evaluasi_id');
            $table->index('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_pertanyaans');
    }
};
