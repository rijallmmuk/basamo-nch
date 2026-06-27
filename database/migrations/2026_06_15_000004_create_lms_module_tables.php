<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LMS — modul belajar & turunannya:
 * - `modules`               : modul (global bila desa_id null, atau milik satu desa).
 * - `module_pages`          : halaman materi (teks/video/pdf) per modul.
 * - `user_module_progress`  : progres belajar warga per modul.
 * - `discussions`           : diskusi/tanya-jawab per modul (threaded).
 * - `xp_logs`               : ledger XP gamifikasi (modul/kuis/diskusi), idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->nullable()->constrained()->nullOnDelete();
            $table->string('judul');
            $table->string('slug');
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->unsignedSmallInteger('estimasi_menit')->nullable(); // estimasi durasi belajar
            $table->foreignId('prasyarat_module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('urutan');
            // Slug unik PER DESA (global = desa_id NULL): dua desa/global boleh judul
            // sama. Bentrok dalam ruang yang sama → Spatie HasSlug auto-suffix (extraScope
            // per desa, cek termasuk yang ter-arsip). Prefix kiri desa_id = index FK.
            $table->unique(['desa_id', 'slug']);
        });

        // Halaman materi = unit penyelesaian; isinya array blok bertipe (teks/video/
        // pdf/gambar/audio/lampiran) pada kolom JSON `blocks` — lihat App\Enums\ModuleBlockType.
        Schema::create('module_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->json('blocks')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('module_id');
            $table->index('urutan');
        });

        // Akuntansi poin ada di xp_logs + users.total_xp (bukan di sini).
        Schema::create('user_module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->json('halaman_selesai')->nullable();
            $table->string('status')->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // unique(user_id, module_id) juga jadi index FK untuk user_id (prefix kiri).
            $table->unique(['user_id', 'module_id']);
            $table->index('module_id');
            $table->index('status');
        });

        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussions')->cascadeOnDelete();
            $table->text('isi');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('parent_id');
            // Daftar thread per modul (parent_id NULL); juga index FK module_id (prefix kiri).
            $table->index(['module_id', 'parent_id'], 'discussions_module_parent_idx');
        });

        // Ledger XP: 1 baris = 1 penghargaan. UNIQUE(user, sumber, sumber_id)
        // memastikan XP per pencapaian hanya diberi sekali (idempotent).
        Schema::create('xp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sumber', 20);        // module | quiz | discussion
            $table->unsignedBigInteger('sumber_id'); // module_id / quiz_id / module_id (diskusi)
            $table->unsignedSmallInteger('jumlah');
            $table->timestamps();

            $table->unique(['user_id', 'sumber', 'sumber_id']);
            $table->index('desa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_logs');
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('user_module_progress');
        Schema::dropIfExists('module_pages');
        Schema::dropIfExists('modules');
    }
};
