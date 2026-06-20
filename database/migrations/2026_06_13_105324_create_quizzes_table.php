<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 modul = 1 kuis (module_id unique). Judul diturunkan: "Kuis: {judul modul}".
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
