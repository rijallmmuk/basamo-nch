<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuis: semua soal setara. Nilai dinormalisasi 0–100 dari jumlah benar.
     * Bobot poin per soal tidak dipakai lagi.
     */
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('points');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn('score_given');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('points')->default(10)->after('question');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->unsignedTinyInteger('score_given')->nullable()->after('is_correct');
        });
    }
};
