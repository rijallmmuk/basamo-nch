<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quiz kini SoftDeletes (setara Modul & Diskusi) — "Hapus" jadi arsip yang bisa
 * dipulihkan, bukan hard-delete permanen yang melenyapkan attempt/jawaban warga.
 * Unique module_id disertai deleted_at agar modul bisa diberi kuis baru setelah
 * kuis lamanya diarsipkan. Komposit ditambah DULU lalu unique lama di-drop, karena
 * indeks lama dipakai FK quizzes_module_id_foreign (sama spt desas.wilayah_kode).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->softDeletes();
            $table->unique(['module_id', 'deleted_at']);
            $table->dropUnique('quizzes_module_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->unique('module_id');
            $table->dropUnique(['module_id', 'deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
