<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesan masuk dari section "Hubungi Kami" situs publik (Jadi Mitra / Keluhan
 * & Saran). Tabel GLOBAL (tanpa nagari_id) — bukan data milik satu nagari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontak_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 20); // App\Enums\KategoriKontak: mitra|keluhan|saran
            $table->string('nama', 150);
            $table->string('email', 150);
            $table->string('no_hp', 20);
            $table->string('nama_nagari', 150)->nullable(); // khusus kategori=mitra
            $table->text('isi');
            $table->timestamps();
            $table->softDeletes();

            // index(kategori) tersendiri TIDAK perlu — sudah ter-cover prefix kiri
            // komposit (kategori, created_at). created_at tersendiri utk retensi/urutan global.
            $table->index('created_at');
            $table->index(['kategori', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontak_masuks');
    }
};
