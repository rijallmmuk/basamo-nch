<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UMKM:
 * - `umkm_categories` : taksonomi UMKM global (dikelola, di-seed langsung di sini).
 * - `umkm_profiles`   : profil usaha milik warga (1 warga = 1 lapak).
 * - `umkm_products`   : produk per usaha; foto via Spatie Media Library (koleksi 'photos').
 */
return new class extends Migration
{
    /** Taksonomi UMKM global awal. */
    /**
     * Kategori bawaan + panduan pengisian deskripsi produk per kategori
     * (satu poin per baris; tampil sebagai panduan & kerangka isian di form
     * produk warga — bisa disunting super admin lewat menu Kategori UMKM).
     */
    private const CATEGORIES = [
        [
            'slug' => 'kuliner', 'nama' => 'Kuliner', 'icon' => 'heroicon-o-cake',
            'panduan_produk' => "Varian/rasa: \nBerat atau isi per kemasan: \nBahan utama: \nDaya tahan & cara penyimpanan: \nLegalitas (PIRT/Halal) bila ada: \nMinimal pesanan / lama pre-order: ",
        ],
        [
            'slug' => 'kerajinan', 'nama' => 'Kerajinan', 'icon' => 'heroicon-o-sparkles',
            'panduan_produk' => "Bahan baku: \nUkuran (panjang × lebar × tinggi): \nWarna/motif tersedia: \nBisa pesan custom atau tidak: \nLama pengerjaan pesanan: ",
        ],
        [
            'slug' => 'fashion', 'nama' => 'Fashion & Tekstil', 'icon' => 'heroicon-o-swatch',
            'panduan_produk' => "Jenis bahan/kain: \nUkuran tersedia (S–XXL atau cm): \nPilihan warna/motif: \nCara perawatan: \nBisa pesan custom/seragam atau tidak: ",
        ],
        [
            'slug' => 'pertanian', 'nama' => 'Pertanian & Perkebunan', 'icon' => 'heroicon-o-sun',
            'panduan_produk' => "Satuan jual (kg/ikat/karung/liter): \nKondisi (segar/kering/olahan): \nKualitas atau grade: \nKetersediaan (stok tetap/musiman): \nMinimal pembelian: ",
        ],
        [
            'slug' => 'peternakan', 'nama' => 'Peternakan & Perikanan', 'icon' => 'heroicon-o-beaker',
            'panduan_produk' => "Satuan jual (kg/ekor/ikat): \nKondisi (hidup/segar/beku/olahan): \nUkuran atau bobot rata-rata: \nKetersediaan (harian/musiman): \nMinimal pembelian: ",
        ],
        [
            'slug' => 'jasa', 'nama' => 'Jasa', 'icon' => 'heroicon-o-briefcase',
            'panduan_produk' => "Rincian layanan yang didapat: \nPerkiraan lama pengerjaan: \nArea jangkauan layanan: \nYang sudah termasuk dalam harga: \nSyarat atau catatan pemesanan: ",
        ],
        [
            'slug' => 'lainnya', 'nama' => 'Lainnya', 'icon' => 'heroicon-o-ellipsis-horizontal-circle',
            'panduan_produk' => "Apa produknya & kegunaannya: \nUkuran/berat/isi: \nBahan atau kondisi: \nKeunggulan dibanding produk sejenis: ",
        ],
    ];

    public function up(): void
    {
        Schema::create('umkm_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon', 60)->nullable();
            // Panduan pengisian deskripsi produk (satu poin per baris) — tampil
            // sebagai panduan + kerangka isian di form produk warga.
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

        Schema::create('umkm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // 1 warga = 1 lapak
            $table->foreignId('umkm_category_id')->nullable()->constrained('umkm_categories')->nullOnDelete();
            $table->string('nama_usaha');
            $table->string('slug')->unique();
            // Tanpa kolom deskripsi profil (keputusan user 2026-07-03) — katalog cukup
            // nama usaha/kategori/alamat; deskripsi ada di tiap PRODUK.
            $table->text('alamat')->nullable();
            $table->string('whatsapp', 20);
            $table->string('status')->default('active');
            // Pengajuan akses UMKM mandiri oleh warga (lihat App\Enums\PengajuanUmkmStatus):
            // null = tak ada pengajuan berjalan; 'menunggu' = antre tinjauan admin desa;
            // 'ditolak' = ditolak (alasan terisi), warga boleh memperbaiki & ajukan ulang.
            $table->string('status_pengajuan', 20)->nullable();
            $table->text('alasan_penolakan_pengajuan')->nullable();
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('desa_id');
            $table->index('status');
            $table->index('status_pengajuan');
        });

        // Foto produk dikelola via Spatie Media Library (koleksi 'photos', maks 5).
        Schema::create('umkm_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_profile_id')->constrained('umkm_profiles')->cascadeOnDelete();
            $table->string('nama_produk');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('harga')->nullable(); // rupiah integer (tanpa pecahan)
            $table->string('status')->default('pending');
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('jumlah_dilihat')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('umkm_profile_id');
            // Katalog publik: WHERE status=approved ORDER BY approved_at.
            $table->index(['status', 'approved_at'], 'umkm_products_status_approved_idx');
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
        Schema::dropIfExists('umkm_profiles');
        Schema::dropIfExists('umkm_categories');
    }
};
