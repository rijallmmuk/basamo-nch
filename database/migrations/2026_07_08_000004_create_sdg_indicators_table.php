<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indikator per sasaran SDGs Desa. `metode`/`target_nilai`/`satuan_acuan`
 * adalah data metodologi resmi Permendesa (bagaimana indikator seharusnya
 * diukur) — disimpan sebagai referensi meski skor aktual kini ditarik
 * langsung dari API Kemendesa per-Poin (lihat `sdg_achievements`), bukan
 * dihitung dari agregasi indikator ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdg_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdg_target_id')->constrained('sdg_targets')->cascadeOnDelete();
            $table->string('kode', 12);                      // '1.1.1'
            $table->text('deskripsi');
            $table->string('metode', 20)->nullable();        // App\Enums\MetodeNilaiIndikator
            $table->decimal('target_nilai', 5, 2)->nullable(); // ambang "100%" utk metode persen (mis. kuota 30%); null = 100 (naik) / 0 (turun)
            $table->string('satuan_acuan', 100)->nullable(); // acuan penyebut kuota, mis. "dari luas wilayah Nagari"
            $table->timestamps();

            $table->unique(['sdg_target_id', 'kode']);
            $table->index('sdg_target_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdg_indicators');
    }
};
