<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Percobaan pengerjaan evaluasi (pre-test maupun Evaluasi Kegiatan) oleh warga. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_percobaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluasi_id')->constrained('evaluasis')->cascadeOnDelete();
            $table->unsignedTinyInteger('nilai')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('evaluasi_id');
            $table->index('status');
            // Cek sudah-lulus / sisa percobaan per user per evaluasi (juga index FK user_id).
            $table->index(['user_id', 'evaluasi_id', 'status'], 'evaluasi_percobaans_user_evaluasi_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_percobaans');
    }
};
