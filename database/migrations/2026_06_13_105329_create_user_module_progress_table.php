<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Akuntansi poin ada di xp_logs + users.total_xp (bukan di sini).
        Schema::create('user_module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->json('pages_completed')->nullable();
            $table->string('status')->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // unique(user_id, module_id) juga jadi index FK untuk user_id (prefix kiri).
            $table->unique(['user_id', 'module_id']);
            $table->index('module_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_module_progress');
    }
};
