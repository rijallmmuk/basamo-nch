<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kolom mati: hanya pernah di-set 0, tak pernah dibaca/ditambah.
        // Akuntansi poin sudah pindah ke xp_logs + users.total_xp.
        Schema::table('user_module_progress', function (Blueprint $table) {
            $table->dropColumn('points_earned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_module_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('points_earned')->default(0)->after('status');
        });
    }
};
