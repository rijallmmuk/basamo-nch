<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas nagari (tenant, selalu "Nagari" — statis); tertaut ke wilayah resmi
 * via `wilayah_kode`. Nama prov/kab/kec disimpan denormalized untuk display
 * cepat.
 *
 * Sekalian memasang FK `users.nagari_id` (kolomnya dibuat di migrasi users,
 * FK ditunda ke sini karena `nagaris` dibuat setelah `users`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nagaris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            // Alamat publik nagari (path /umkm/{slug}; kelak subdomain) — unik, digenerate model boot.
            $table->string('slug', 160);
            // Tautan ke wilayah resmi level nagari/kelurahan (Kepmendagri). Nullable.
            $table->string('wilayah_kode', 13)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_lng', 11, 8)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('wilayah_kode')->references('kode')->on('ref_wilayah')->nullOnDelete();
            // Index lookup polos: wilayah_kode utk FK/pencarian, slug utk resolusi
            // subdomain publik. Keunikan TIDAK dijaga di sini — unique komposit
            // (kolom, deleted_at) TIDAK menahan duplikat baris aktif di MariaDB
            // (NULL != NULL), maka keunikan aktif ditegakkan kolom virtual di bawah.
            $table->index('wilayah_kode');
            $table->index('slug');
        });

        // Keunikan HANYA pada baris aktif (deleted_at NULL): kode/slug nagari terarsip
        // boleh dipakai ulang nagari baru, tapi mustahil ada 2 nagari AKTIF berkode/
        // ber-slug sama (kode sama = 2 admin ber-username sama → login ambigu).
        // CASE WHEN portable MariaDB & SQLite (pola sama: users.active_username).
        Schema::table('nagaris', function (Blueprint $table) {
            $table->string('active_wilayah_kode', 13)
                ->nullable()
                ->virtualAs('case when deleted_at is null then wilayah_kode else null end')
                ->unique();
            $table->string('active_slug', 160)
                ->nullable()
                ->virtualAs('case when deleted_at is null then slug else null end')
                ->unique();
        });

        // FK users → nagaris (kolom dibuat lebih dulu di migrasi users). Index FK
        // nagari_id dipenuhi komposit users_nagari_role_status_xp_idx (prefix kiri).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('nagari_id')->references('id')->on('nagaris')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['nagari_id']);
        });

        Schema::dropIfExists('nagaris');
    }
};
