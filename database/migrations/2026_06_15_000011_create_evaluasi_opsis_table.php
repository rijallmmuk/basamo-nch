<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Opsi jawaban; boleh >1 benar (pilihan jamak). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_opsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pertanyaan_id')->constrained('evaluasi_pertanyaans')->cascadeOnDelete();
            $table->text('teks_opsi');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('pertanyaan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_opsis');
    }
};
