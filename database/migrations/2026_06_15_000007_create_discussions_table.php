<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Diskusi/tanya-jawab per modul (threaded). Diskusi HANYA ada pada level modul. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussions')->cascadeOnDelete();
            $table->text('isi');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();
            // Terhapus sebagai imbas modul/program induk dihapus, bukan dihapus langsung.
            $table->timestamp('cascade_deleted_at')->nullable()->index();

            $table->index('user_id');
            $table->index('parent_id');
            $table->index(['module_id', 'parent_id'], 'discussions_module_parent_idx');
        });

        // Penegasan eksplisit "diskusi selalu punya wadah modul". Redundan dengan
        // NOT NULL di atas, tetapi sengaja dipertahankan sebagai jaring pengaman
        // bila kelak kolomnya diubah nullable tanpa sengaja.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE discussions ADD CONSTRAINT discussions_module_id_check '
                .'CHECK (module_id IS NOT NULL)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discussions');
    }
};
