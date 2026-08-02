<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Profil usaha milik warga (1 warga = 1 lapak). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // 1 warga = 1 lapak
            $table->string('nama_usaha');
            $table->string('slug')->unique();
            // Etalase "rumah" pemilik UMKM (2026-07-23) — profil kaya untuk promosi;
            // membalik keputusan 2026-07-03 (dulu tanpa deskripsi) atas permintaan klien.
            $table->text('deskripsi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('whatsapp', 20);
            $table->unsignedInteger('jumlah_dilihat')->default(0);
            $table->string('email')->nullable();
            $table->string('jam_operasional')->nullable();
            $table->unsignedSmallInteger('tahun_berdiri')->nullable();
            // Tautan promosi (sosmed & e-commerce yang dimiliki): array {platform, url}.
            // Logo/sampul/QR disimpan via Spatie Media Library (bukan kolom).
            $table->json('tautan')->nullable();
            // Akses UMKM HANYA diberikan operator nagari/superadmin (pengajuan mandiri
            // warga dihapus 2026-07-23) → tak ada lagi kolom status_pengajuan.
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('nagari_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_profiles');
    }
};
