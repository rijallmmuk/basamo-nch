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
     * Kategori bawaan + bantuan deskripsi produk per kategori — sengaja UMUM
     * saja (3 poin panduan + 1 contoh deskripsi jadi); detail lanjutan biar
     * ditanyakan pembeli lewat WhatsApp penjual. Panduan: satu poin per baris.
     * Keduanya bisa disunting super admin lewat menu Kategori UMKM.
     */
    private const CATEGORIES = [
        [
            'slug' => 'kuliner', 'nama' => 'Kuliner',
            'panduan_produk' => "Varian atau rasa yang tersedia\nBerat atau isi per kemasan\nApa yang membuatnya istimewa",
            'contoh_deskripsi' => 'Keripik singkong balado renyah dengan bumbu racikan sendiri. Tersedia rasa original dan pedas, kemasan 250 gram. Dibuat dari bahan pilihan tanpa pengawet.',
        ],
        [
            'slug' => 'kerajinan', 'nama' => 'Kerajinan',
            'panduan_produk' => "Bahan baku yang dipakai\nUkuran produk\nBisa pesan custom atau tidak",
            'contoh_deskripsi' => 'Tas anyaman pandan buatan tangan, ukuran 30 × 25 cm. Tersedia beberapa pilihan motif, bisa juga pesan sesuai keinginan. Kuat dan rapi untuk dipakai sehari-hari.',
        ],
        [
            'slug' => 'fashion', 'nama' => 'Fashion & Tekstil',
            'panduan_produk' => "Jenis bahan atau kain\nUkuran yang tersedia\nPilihan warna atau motif",
            'contoh_deskripsi' => 'Baju kurung bahan katun yang adem dan nyaman dipakai. Tersedia ukuran S sampai XL dengan beberapa pilihan warna. Jahitan rapi, cocok untuk acara resmi maupun harian.',
        ],
        [
            'slug' => 'pertanian', 'nama' => 'Pertanian & Perkebunan',
            'panduan_produk' => "Satuan jual (kg, ikat, karung)\nKondisi (segar, kering, olahan)\nApa keunggulannya",
            'contoh_deskripsi' => 'Beras sipulen hasil panen sendiri, dijual per karung 10 kg. Butiran utuh dan pulen saat dimasak. Stok tersedia setiap bulan.',
        ],
        [
            'slug' => 'peternakan', 'nama' => 'Peternakan & Perikanan',
            'panduan_produk' => "Satuan jual (kg, ekor, ikat)\nKondisi (hidup, segar, beku)\nUkuran atau bobot rata-rata",
            'contoh_deskripsi' => 'Ikan nila segar langsung dari kolam sendiri, dijual per kg. Ukuran rata-rata 3–4 ekor per kg. Bisa dibersihkan dulu sesuai permintaan.',
        ],
        [
            'slug' => 'jasa', 'nama' => 'Jasa',
            'panduan_produk' => "Layanan apa yang didapat\nPerkiraan lama pengerjaan\nApa saja yang sudah termasuk harga",
            'contoh_deskripsi' => 'Jasa jahit pakaian wanita dan seragam sekolah. Pengerjaan sekitar satu minggu, harga sudah termasuk benang dan kancing. Hasil rapi, bisa disesuaikan bila kurang pas.',
        ],
        [
            'slug' => 'lainnya', 'nama' => 'Lainnya',
            'panduan_produk' => "Apa produknya dan kegunaannya\nUkuran, berat, atau isi\nApa yang membuatnya istimewa",
            'contoh_deskripsi' => 'Sabun cuci piring buatan sendiri, kemasan botol 500 ml. Busa melimpah dengan wangi jeruk nipis, ampuh mengangkat lemak. Lebih hemat dibanding merek pabrik.',
        ],
    ];

    public function up(): void
    {
        Schema::create('umkm_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('slug', 100)->unique();
            // Bantuan deskripsi produk di form warga — umum saja, detail via
            // WhatsApp: panduan (satu poin per baris) + contoh deskripsi jadi
            // yang bisa dipakai sekali klik lalu diubah seperlunya.
            $table->text('panduan_produk')->nullable();
            $table->text('contoh_deskripsi')->nullable();
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
