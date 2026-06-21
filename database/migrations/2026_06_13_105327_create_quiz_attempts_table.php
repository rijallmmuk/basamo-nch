<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('status');
            // Cek sudah-lulus / sisa percobaan per user per kuis (juga index FK user_id).
            $table->index(['user_id', 'quiz_id', 'status'], 'quiz_attempts_user_quiz_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
