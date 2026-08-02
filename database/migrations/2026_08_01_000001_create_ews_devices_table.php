<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perangkat EWS (Early Warning System) banjir bandang milik sebuah nagari: satu
 * panel sensor Blynk berisi tinggi air, curah hujan, pH air, getaran, dan status
 * sungai. Pilar 4 "Medan Nan Bapaneh".
 *
 * SATU NAGARI SATU PERANGKAT (`nagari_id` unique). Kalau kelak sebuah nagari
 * memasang lebih dari satu titik pantau, unique ini yang dilonggarkan; sampai itu
 * terjadi, unique-lah yang menjamin halaman publik nagari tidak pernah bingung
 * memilih di antara dua panel.
 *
 * `blynk_token` DISIMPAN TERENKRIPSI (cast `encrypted` di model). Token Blynk
 * bukan kredensial baca-saja: endpoint `update` memakai token yang sama, jadi
 * siapa pun yang memegangnya dapat menulis nilai palsu ke perangkat peringatan
 * dini banjir. Karena itu ia tidak pernah dikirim ke peramban, tidak pernah masuk
 * repo, dan tidak terbaca walau basis datanya bocor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            // Nama titik pantau apa adanya di lapangan, mis. "Sungai Batang Sinamar".
            // Boleh kosong: tampilan jatuh ke nama nagarinya.
            $table->string('nama_lokasi')->nullable();
            $table->text('blynk_token');
            // Nonaktif = berhenti dipanggil penjadwal dan hilang dari halaman publik,
            // tanpa kehilangan riwayat pembacaannya.
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique('nagari_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_devices');
    }
};
