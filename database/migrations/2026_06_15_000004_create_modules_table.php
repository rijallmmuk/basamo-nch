<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul SLC = satu "buku ajar" di dalam sebuah PELAKSANAAN pelatihan. Setiap modul
 * WAJIB bernaung pada satu pelaksanaan (`pelatihan_id` NOT NULL); konsep "Modul Umum"
 * yang berdiri sendiri sudah dihapus.
 *
 * SASARAN NAGARI selalu diwarisi dari pelaksanaan (pivot `pelatihan_nagari` atau
 * `pelatihans.semua_nagari`). Modul tidak punya sasaran sendiri: satu sumber kebenaran.
 *
 * Modul TIDAK punya status sendiri. Gerbang akses warga hanya satu, yaitu status
 * pelatihannya (App\Enums\StatusPelatihan). Modul tanpa materi otomatis tersembunyi
 * lewat Module::scopeReady(), jadi saklar kedua tidak diperlukan.
 *
 * FK `pelatihan_id` dipasang di create_pelatihans_table (tabelnya dibuat belakangan)
 * dengan cascadeOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            // NOT NULL: modul selalu punya induk. FK di create_pelatihans_table.
            $table->unsignedBigInteger('pelatihan_id');
            $table->string('judul');
            $table->string('slug');
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->foreignId('prasyarat_module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            // Ditandai saat modul terhapus sebagai imbas pelaksanaan induknya dihapus,
            // bukan dihapus langsung — dipakai untuk memulihkan bersama induknya.
            $table->timestamp('cascade_deleted_at')->nullable()->index();

            $table->index('pelatihan_id');
            $table->index('urutan');
            $table->index('created_by');
            $table->index(['pelatihan_id', 'urutan', 'id']);
            // Slug unik GLOBAL: satu pelaksanaan bisa menyasar banyak nagari, jadi ruang
            // slug tak bisa di-scope per nagari. Bentrok → Spatie HasSlug auto-suffix
            // (cek termasuk baris ter-arsip).
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
