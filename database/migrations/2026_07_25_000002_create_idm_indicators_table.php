<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian ~50 indikator IDM per status (per nagari per tahun) — keterbukaan
 * informasi: skor 0-5, kondisi, rekomendasi kegiatan, dan sumber dana. Snapshot
 * ganti-total saat refresh (mengikuti API), TANPA soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idm_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idm_status_id')->constrained('idm_statuses')->cascadeOnDelete();
            $table->string('dimensi', 3);                       // App\Enums\DimensiIdm (IKS/IKE/IKL)
            $table->unsignedSmallInteger('nomor');              // nomor indikator dalam dimensinya
            $table->string('indikator', 150);
            $table->unsignedTinyInteger('skor');                // 0..5
            $table->text('keterangan')->nullable();             // kondisi indikator saat ini
            $table->text('kegiatan')->nullable();               // "kegiatan yang dapat dilakukan" (bila lemah)
            $table->decimal('nilai', 8, 6)->nullable();         // "+NILAI": penambahan indeks bila kegiatan dilakukan
            $table->json('pelaksana')->nullable();              // "yang dapat melaksanakan kegiatan" {level: instansi}
            $table->timestamps();

            $table->unique(['idm_status_id', 'dimensi', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idm_indicators');
    }
};
