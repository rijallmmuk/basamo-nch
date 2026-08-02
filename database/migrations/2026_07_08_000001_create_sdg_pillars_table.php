<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SDGs Desa (Permendesa PDTT 13/2025). Referensi
 * global (tanpa nagari_id): pilar → poin (goal) → sasaran (target) →
 * indikator — berperan sebagai PANDUAN pengisian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdg_pillars', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->string('slug', 50)->unique();
            $table->string('warna', 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdg_pillars');
    }
};
