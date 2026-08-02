<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Taksonomi UMKM global (dikelola super admin, di-seed langsung di sini).
 * Kategori bawaan + panduan menulis deskripsi produk per kategori, sengaja UMUM
 * saja (tiga poin); detail lanjutan biar ditanyakan pembeli lewat WhatsApp
 * penjual. Satu poin per baris, ditampilkan pada form produk milik pemilik.
 */
return new class extends Migration
{
    private const CATEGORIES = [
        [
            'slug' => 'kuliner', 'nama' => 'Kuliner',
            'panduan_produk' => "Varian atau rasa yang tersedia\nBerat atau isi per kemasan\nApa yang membuatnya istimewa",
        ],
        [
            'slug' => 'kerajinan', 'nama' => 'Kerajinan',
            'panduan_produk' => "Bahan baku yang dipakai\nUkuran produk\nBisa pesan custom atau tidak",
        ],
        [
            'slug' => 'fashion', 'nama' => 'Fashion & Tekstil',
            'panduan_produk' => "Jenis bahan atau kain\nUkuran yang tersedia\nPilihan warna atau motif",
        ],
        [
            'slug' => 'pertanian', 'nama' => 'Pertanian & Perkebunan',
            'panduan_produk' => "Satuan jual (kg, ikat, karung)\nKondisi (segar, kering, olahan)\nApa keunggulannya",
        ],
        [
            'slug' => 'peternakan', 'nama' => 'Peternakan & Perikanan',
            'panduan_produk' => "Satuan jual (kg, ekor, ikat)\nKondisi (hidup, segar, beku)\nUkuran atau bobot rata-rata",
        ],
        [
            'slug' => 'jasa', 'nama' => 'Jasa',
            'panduan_produk' => "Layanan apa yang didapat\nPerkiraan lama pengerjaan\nApa saja yang sudah termasuk harga",
        ],
        [
            'slug' => 'lainnya', 'nama' => 'Lainnya',
            'panduan_produk' => "Apa produknya dan kegunaannya\nUkuran, berat, atau isi\nApa yang membuatnya istimewa",
        ],
    ];

    public function up(): void
    {
        Schema::create('umkm_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('slug', 100)->unique();
            $table->text('panduan_produk')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        foreach (self::CATEGORIES as $i => $cat) {
            DB::table('umkm_categories')->insert([
                ...$cat,
                'urutan' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_categories');
    }
};
