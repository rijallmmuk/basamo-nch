<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sasaran per Poin SDGs Desa (Lampiran I/II Permendesa 13/2025). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdg_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdg_goal_id')->constrained('sdg_goals')->cascadeOnDelete();
            $table->string('kode', 10);                      // '1.1'
            $table->text('deskripsi');
            $table->string('sub_tema', 20)->nullable();      // poin 18: kelembagaan|budaya (Lampiran II)
            $table->timestamps();

            $table->unique(['sdg_goal_id', 'kode']);
            $table->index('sdg_goal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdg_targets');
    }
};
