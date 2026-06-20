<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index komposit untuk query panas skala nasional. Tiap komposit menggantikan
 * index satu-kolom yang jadi redundan (prefix kiri) → tanpa bloat.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tambah komposit DULU agar tetap menutup kebutuhan index FK saat single di-drop.
        Schema::table('users', function (Blueprint $table) {
            // Leaderboard: WHERE nagari,role,status ORDER BY total_xp (filter + sort 1 index).
            $table->index(['nagari_id', 'role', 'status', 'total_xp'], 'users_nagari_role_status_xp_idx');
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Cek sudah-lulus / sisa percobaan per user per kuis.
            $table->index(['user_id', 'quiz_id', 'status'], 'quiz_attempts_user_quiz_status_idx');
        });
        Schema::table('notifications', function (Blueprint $table) {
            // Hitung unread per user (dipanggil tiap page-load portal).
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_idx');
        });
        Schema::table('umkm_products', function (Blueprint $table) {
            // Katalog publik: WHERE status=approved ORDER BY approved_at.
            $table->index(['status', 'approved_at'], 'umkm_products_status_approved_idx');
        });
        Schema::table('discussions', function (Blueprint $table) {
            // Daftar thread per modul (parent_id NULL).
            $table->index(['module_id', 'parent_id'], 'discussions_module_parent_idx');
        });

        // Buang index satu-kolom yang kini jadi prefix kiri dari komposit/unique.
        Schema::table('users', fn (Blueprint $t) => $t->dropIndex('users_nagari_id_index'));
        Schema::table('quiz_attempts', fn (Blueprint $t) => $t->dropIndex('quiz_attempts_user_id_index'));
        Schema::table('notifications', fn (Blueprint $t) => $t->dropIndex('notifications_notifiable_type_notifiable_id_index'));
        Schema::table('umkm_products', fn (Blueprint $t) => $t->dropIndex('umkm_products_status_index'));
        Schema::table('discussions', fn (Blueprint $t) => $t->dropIndex('discussions_module_id_index'));
        Schema::table('user_module_progress', fn (Blueprint $t) => $t->dropIndex('user_module_progress_user_id_index'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->index('nagari_id', 'users_nagari_id_index'));
        Schema::table('quiz_attempts', fn (Blueprint $t) => $t->index('user_id', 'quiz_attempts_user_id_index'));
        Schema::table('notifications', fn (Blueprint $t) => $t->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_type_notifiable_id_index'));
        Schema::table('umkm_products', fn (Blueprint $t) => $t->index('status', 'umkm_products_status_index'));
        Schema::table('discussions', fn (Blueprint $t) => $t->index('module_id', 'discussions_module_id_index'));
        Schema::table('user_module_progress', fn (Blueprint $t) => $t->index('user_id', 'user_module_progress_user_id_index'));

        Schema::table('users', fn (Blueprint $t) => $t->dropIndex('users_nagari_role_status_xp_idx'));
        Schema::table('quiz_attempts', fn (Blueprint $t) => $t->dropIndex('quiz_attempts_user_quiz_status_idx'));
        Schema::table('notifications', fn (Blueprint $t) => $t->dropIndex('notifications_notifiable_read_idx'));
        Schema::table('umkm_products', fn (Blueprint $t) => $t->dropIndex('umkm_products_status_approved_idx'));
        Schema::table('discussions', fn (Blueprint $t) => $t->dropIndex('discussions_module_parent_idx'));
    }
};
