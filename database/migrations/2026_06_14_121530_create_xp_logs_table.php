<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger XP: 1 baris = 1 penghargaan. UNIQUE(user, source, source_id)
     * memastikan XP per pencapaian hanya diberi sekali (idempotent).
     */
    public function up(): void
    {
        Schema::create('xp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20);        // module | quiz | discussion
            $table->unsignedBigInteger('source_id'); // module_id / quiz_id / module_id (diskusi)
            $table->unsignedSmallInteger('amount');
            $table->timestamps();

            $table->unique(['user_id', 'source', 'source_id']);
            $table->index('desa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_logs');
    }
};
