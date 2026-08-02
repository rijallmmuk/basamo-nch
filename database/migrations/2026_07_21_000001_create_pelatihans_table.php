<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelatihan = milik PENGAJAR PEMBUATNYA (tema + nagari sasaran + status). Pengajar yang
 * hendak menaruh modul membuat pelatihannya lebih dulu bila belum ada.
 *
 * TIDAK punya tahun. Gerbang akses warga ada DI SINI (`status`), dan modul di dalamnya
 * sengaja tanpa status agar tidak ada dua saklar untuk satu keputusan.
 *
 * Tidak punya nama/judul manual (nama tampil dirakit dari tema + sasaran). Deskripsi
 * dan cover OPSIONAL, sama seperti modul; bila cover tidak diunggah, kartu memakai
 * gambar yang digambar otomatis dari nama temanya.
 *
 * `nagari_id` = nagari PENYELENGGARA, dipakai scoping operator (NULL = lintas nagari).
 * SASARAN audiens ada di pivot `pelatihan_nagari` atau `semua_nagari` = true, dan
 * seluruh modul di dalamnya mewarisinya.
 *
 * Sekalian memasang FK `modules.pelatihan_id` (kolomnya dibuat di migrasi modules;
 * FK ditunda ke sini karena `pelatihans` dibuat belakangan — pola sama users→nagaris).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelatihans', function (Blueprint $table) {
            $table->id();
            // Tema dipakai bersama: pelaksanaan menahan penghapusan temanya.
            $table->foreignId('tema_pelatihan_id')->constrained('tema_pelatihans')->restrictOnDelete();
            $table->foreignId('nagari_id')->nullable()->constrained()->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // SATU-SATUNYA gerbang akses warga (App\Enums\StatusPelatihan):
            // terkunci = tampil "Belum dibuka", terbuka = bisa dipelajari.
            $table->string('status', 20)->default('terkunci');
            // true = menyasar SEMUA nagari secara DINAMIS (nagari baru otomatis ikut,
            // pivot diabaikan); false = hanya nagari tertentu di pivot pelatihan_nagari.
            $table->boolean('semua_nagari')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tema_pelatihan_id');
            $table->index('nagari_id');
            $table->index('created_by');
            $table->index('status');
        });

        Schema::table('modules', function (Blueprint $table) {
            // Hapus PERMANEN pelaksanaan → modulnya (dan seluruh materi/evaluasi/diskusinya)
            // ikut terhapus. Soft-delete di-cascade lewat model event (lihat Pelatihan::booted).
            $table->foreign('pelatihan_id')->references('id')->on('pelatihans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['pelatihan_id']);
        });

        Schema::dropIfExists('pelatihans');
    }
};
