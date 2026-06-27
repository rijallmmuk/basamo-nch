<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LMS — kuis modul:
 * - `quizzes`        : 1 modul = 1 kuis (module_id unique).
 * - `quiz_questions` : soal pilihan ganda (semua soal setara, tanpa bobot).
 * - `quiz_options`   : opsi jawaban; boleh >1 benar (pilihan jamak).
 * - `quiz_attempts`  : percobaan pengerjaan warga.
 * - `quiz_answers`   : 1 baris per opsi terpilih (mendukung partial credit).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Judul kuis diturunkan dari modulnya: "Kuis: {judul modul}".
        // SoftDeletes: "Hapus" = arsip (tak melenyapkan attempt/jawaban warga). Unique
        // (module_id, deleted_at) → modul bisa diberi kuis baru setelah kuis lama diarsip;
        // prefix kiri module_id sekaligus memenuhi index FK.
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('nilai_lulus')->default(70);
            $table->unsignedTinyInteger('maks_percobaan')->default(3);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['module_id', 'deleted_at']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('pertanyaan');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('urutan');
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->text('teks_opsi');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('question_id');
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('nilai')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('status');
            // Cek sudah-lulus / sisa percobaan per user per kuis (juga index FK user_id).
            $table->index(['user_id', 'quiz_id', 'status'], 'quiz_attempts_user_quiz_status_idx');
        });

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
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
