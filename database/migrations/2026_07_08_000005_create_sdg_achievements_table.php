<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Transaksi per nagari: capaian % per Poin, ditarik dari API Kemendesa. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdg_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sdg_goal_id')->constrained('sdg_goals')->cascadeOnDelete();
            $table->decimal('persentase', 5, 2)->nullable(); // 0..100, ditarik dari API Kemendesa (potret berjalan, TANPA dimensi tahun)
            $table->timestamp('fetched_at')->nullable();     // kapan baris ini terakhir diperbarui dari API
            $table->timestamps();

            // Data cermin API (upsert via updateOrCreate, tak pernah dihapus) → TANPA
            // soft delete. Unique ketat = penjaga idempoten level-DB: mustahil 2 baris
            // utk (nagari, poin) yang sama walau job refresh jalan bersamaan.
            $table->unique(['nagari_id', 'sdg_goal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdg_achievements');
    }
};
