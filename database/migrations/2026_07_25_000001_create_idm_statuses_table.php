<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status IDM (Indeks Desa Membangun) per nagari per TAHUN, ditarik dari API Kemendesa
 * (mapData.SUMMARIES). Cermin API: upsert via updateOrCreate, tak pernah dihapus manual
 * → TANPA soft delete. Unique (nagari, tahun) = penjaga idempoten level-DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idm_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->decimal('skor', 6, 4)->nullable();          // SKOR_SAAT_INI (indeks IDM 0-1)
            $table->string('status', 30);                       // STATUS (mis. BERKEMBANG) — App\Enums\StatusIdm
            $table->string('target_status', 30)->nullable();    // TARGET_STATUS
            $table->decimal('skor_minimal', 6, 4)->nullable();  // ambang skor status berikutnya
            $table->decimal('penambahan', 8, 6)->nullable();    // selisih menuju target
            // Tiga sub-indeks penyusun IDM (rata-ratanya = skor IDM) — dari baris subtotal API.
            $table->decimal('skor_iks', 6, 4)->nullable();      // Indeks Ketahanan Sosial
            $table->decimal('skor_ike', 6, 4)->nullable();      // Indeks Ketahanan Ekonomi
            $table->decimal('skor_ikl', 6, 4)->nullable();      // Indeks Ketahanan Lingkungan
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['nagari_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idm_statuses');
    }
};
