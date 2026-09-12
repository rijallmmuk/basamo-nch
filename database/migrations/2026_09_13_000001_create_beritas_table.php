<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beritas', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('konten');
            $table->string('kategori', 30)->default('berita');
            $table->string('status', 30)->default('diterbitkan');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('penulis_nama')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('nagari_id')->nullable()->constrained('nagaris')->cascadeOnDelete();
            $table->boolean('semua_nagari')->default(false);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['nagari_id', 'status']);
            $table->index(['is_pinned', 'published_at']);
            $table->index('kategori');
        });

        Schema::create('berita_nagari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_id')->constrained('beritas')->cascadeOnDelete();
            $table->foreignId('nagari_id')->constrained('nagaris')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['berita_id', 'nagari_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_nagari');
        Schema::dropIfExists('beritas');
    }
};
