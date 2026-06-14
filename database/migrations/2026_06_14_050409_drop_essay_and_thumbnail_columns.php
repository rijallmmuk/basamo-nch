<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuis ditetapkan hanya pilihan ganda; modul tanpa thumbnail.
     * Bersihkan data essay lama lalu hapus kolom yang tak terpakai.
     */
    public function up(): void
    {
        // Bersihkan data essay (cascade ke options & answers via FK)
        DB::table('quiz_questions')->where('type', 'essay')->delete();

        // Tidak ada lagi status menunggu review — anggap gagal
        DB::table('quiz_attempts')->where('status', 'pending_review')->update(['status' => 'failed']);

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('thumbnail');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn(['answer_text', 'feedback']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['reviewed_at', 'reviewed_by']);
        });

        // Status kuis kini hanya: in_progress, passed, failed (enum khusus MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_attempts MODIFY COLUMN status ENUM('in_progress', 'passed', 'failed') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_attempts MODIFY COLUMN status ENUM('in_progress', 'pending_review', 'passed', 'failed') NOT NULL");
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('question_id');
            $table->text('feedback')->nullable()->after('score_given');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->enum('type', ['multiple_choice', 'essay'])->default('multiple_choice')->after('question');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->after('description');
        });
    }
};
