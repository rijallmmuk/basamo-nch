<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto produk dikelola via Spatie Media Library (koleksi 'photos', maks 5).
        Schema::create('umkm_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_profile_id')->constrained('umkm_profiles')->cascadeOnDelete();
            $table->string('nama_produk');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('harga')->nullable(); // rupiah integer (tanpa pecahan)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('umkm_profile_id');
            // Katalog publik: WHERE status=approved ORDER BY approved_at.
            $table->index(['status', 'approved_at'], 'umkm_products_status_approved_idx');
        });

        // FULLTEXT pencarian katalog — hanya MySQL/MariaDB (lewati sqlite di test).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
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
