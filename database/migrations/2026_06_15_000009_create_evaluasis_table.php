<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluasi modul, dua jenis (App\Enums\JenisEvaluasi):
 *
 *  - `pretest`  → gerbang OPSIONAL sebelum materi terbuka. Tanpa syarat nilai
 *                 minimum, maksimal SATU percobaan, nilainya disimpan sebagai
 *                 data awal pembanding.
 *  - `kegiatan` → Evaluasi Kegiatan, penutup modul (dulu bernama "kuis").
 *
 * Invariant "1 modul = 1 evaluasi AKTIF per jenis" ditegakkan unique gabungan
 * (active_module_id, jenis). Kolom virtual `active_module_id` bernilai NULL saat
 * baris ter-arsip, sehingga arsip tak menahan pembuatan evaluasi pengganti —
 * unique komposit ber-`deleted_at` TIDAK dipakai karena di MariaDB NULL != NULL
 * membuatnya jadi penjaga palsu (pola sama: nagaris.active_slug, users.active_username).
 *
 * TANPA saklar terbit/draft. Evaluasi dianggap siap begitu soalnya lengkap
 * ({@see Evaluasi::scopeReady()}); gerbang akses warga tetap satu, yaitu status pelatihan.
 *
 * SoftDeletes: "Hapus" = arsip, tidak melenyapkan percobaan/jawaban warga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('jenis', 20)->default('kegiatan'); // App\Enums\JenisEvaluasi
            $table->unsignedTinyInteger('nilai_lulus')->default(70);
            // 0 = percobaan tak dibatasi. Pre-test dipaksa 1 oleh model.
            $table->unsignedTinyInteger('maks_percobaan')->default(0);
            // Penanda idempoten agar notifikasi "evaluasi siap" tidak terkirim dua kali.
            $table->timestamp('ready_notified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // Terhapus sebagai imbas modul/pelaksanaan induk dihapus, bukan dihapus langsung.
            $table->timestamp('cascade_deleted_at')->nullable()->index();

            $table->index('module_id');
            $table->index(['module_id', 'jenis']);
        });

        Schema::table('evaluasis', function (Blueprint $table) {
            $table->unsignedBigInteger('active_module_id')
                ->nullable()
                ->virtualAs('case when deleted_at is null then module_id else null end');

            $table->unique(['active_module_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasis');
    }
};
