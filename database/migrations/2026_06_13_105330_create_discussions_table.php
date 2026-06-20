<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussions')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('parent_id');
            // Daftar thread per modul (parent_id NULL); juga index FK module_id (prefix kiri).
            $table->index(['module_id', 'parent_id'], 'discussions_module_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussions');
    }
};
