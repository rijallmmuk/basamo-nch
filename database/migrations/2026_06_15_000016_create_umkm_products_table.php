<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produk per usaha; foto via Spatie Media Library (koleksi 'photos').
 *
 * TANPA status terbit dan TANPA soft delete (2026-07-31): satu produk hanya punya
 * satu keadaan, yaitu tampil selama lapaknya aktif. Menghapus produk berarti
 * benar-benar menghapusnya beserta foto-fotonya, bukan menyembunyikannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_profile_id')->constrained('umkm_profiles')->cascadeOnDelete();
            // Kategori menempel di PRODUK, bukan profil (keputusan user 2026-07-03):
            // satu lapak boleh menjual produk lintas kategori — pola marketplace.
            $table->foreignId('umkm_category_id')->nullable()->constrained('umkm_categories')->nullOnDelete();
            $table->string('nama_produk');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            // Tautan promosi produk (sosmed/marketplace tempat produk dijual): array {platform, url}.
            $table->json('tautan')->nullable();
            $table->unsignedBigInteger('harga')->nullable(); // rupiah integer (tanpa pecahan)
            $table->unsignedInteger('jumlah_dilihat')->default(0);
            $table->timestamps();

            $table->index('umkm_profile_id');
            // Katalog publik mengurutkan produk lapak menurut waktu buat.
            $table->index(['umkm_profile_id', 'created_at'], 'umkm_products_profile_created_idx');
        });

        // FULLTEXT pencarian katalog — hanya MySQL/MariaDB (lewati sqlite di test).
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('umkm_products', function (Blueprint $table) {
                $table->fullText(['nama_produk', 'deskripsi'], 'umkm_products_search_fulltext');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_products');
    }
};
