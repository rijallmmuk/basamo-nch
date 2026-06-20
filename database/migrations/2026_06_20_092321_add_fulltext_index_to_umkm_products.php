<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index FULLTEXT untuk pencarian katalog publik. Menggantikan LIKE '%q%'
     * (leading wildcard = full scan) agar tetap cepat di skala nasional.
     */
    public function up(): void
    {
        // FULLTEXT hanya didukung MySQL/MariaDB. Lewati di sqlite (test).
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('umkm_products', function (Blueprint $table) {
            $table->fullText(['nama_produk', 'deskripsi'], 'umkm_products_search_fulltext');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('umkm_products', function (Blueprint $table) {
            $table->dropFullText('umkm_products_search_fulltext');
        });
    }
};
