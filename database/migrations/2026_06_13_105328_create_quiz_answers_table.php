<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 baris per opsi terpilih (mendukung soal multi-jawaban / partial credit).
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('quiz_options')->nullOnDelete();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->index('attempt_id');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
