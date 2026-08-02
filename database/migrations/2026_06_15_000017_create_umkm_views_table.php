<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekap kunjungan HARIAN etalase usaha dan detail produk.
 *
 * `umkm_profiles.jumlah_dilihat` dan `umkm_products.jumlah_dilihat` hanya menyimpan
 * satu angka seumur hidup, sehingga pemilik lapak tidak pernah tahu apakah lapaknya
 * sedang ramai atau sepi. Tabel ini menambahkan sumbu waktu yang hilang itu.
 *
 * Yang disimpan adalah REKAP per hari, bukan satu baris per klik: kunjungan yang
 * lolos deduplikasi menaikkan `jumlah` pada baris hari itu. Satu produk paling
 * banyak menghasilkan satu baris sehari, berapa pun ramainya.
 *
 * Pencatatan dimulai sejak tabel ini ada; kunjungan lama tidak punya tanggal dan
 * tidak dapat diurai balik. Karena itu angka seumur hidup TETAP dipakai untuk
 * total, dan tabel ini hanya untuk tren serta rentang waktu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_views', function (Blueprint $table) {
            $table->id();
            // Polimorfik: yang dikunjungi bisa etalase usaha atau detail produk.
            $table->morphs('viewable');
            $table->date('tanggal');
            $table->unsignedInteger('jumlah')->default(0);
            $table->timestamps();

            // Penjaga rekap: satu baris per entitas per hari. Sekaligus jalur
            // upsert-nya, jadi penambahan kunjungan tidak perlu membaca dulu.
            $table->unique(['viewable_type', 'viewable_id', 'tanggal'], 'umkm_views_harian_unik');
            // Grafik selalu bertanya "rentang tanggal sekian", lintas entitas.
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_views');
    }
};
