<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akun login (superadmin/operator/warga) — lihat App\Models\User.
 *
 * `nagari_id`/`penduduk_id` dibuat di sini TANPA constraint FK (tabel `nagaris`/
 * `penduduk` belum ada — dibuat setelah `users`). FK sesungguhnya dipasang di
 * migrasi tabel terkait (`create_nagaris_table`/`create_penduduk_table`) begitu
 * tabel tujuannya sudah ada. Ini satu-satunya bagian skema `users` yang secara
 * teknis "tinggal" di file lain — sisanya (semua kolom & index) lengkap di sini.
 *
 * `active_username` (generated/stored) menegakkan keunikan username HANYA pada
 * baris aktif (deleted_at NULL) — username admin terarsip boleh dipakai ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nagari_id')->nullable();      // nullable untuk superadmin; FK di create_nagaris_table
            $table->string('name');
            // NIK kanonik ada di `penduduk`; di-mirror ke sini sbg kunci login warga.
            $table->string('nik', 16)->nullable()->unique();
            $table->unsignedBigInteger('penduduk_id')->nullable();    // FK di create_penduduk_table
            // username (kode nagari operator nagari) nullable; keunikan aktif dijaga via active_username.
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();         // opsional: kontak, bukan identitas utama
            $table->string('phone', 20)->nullable();               // No. WhatsApp/HP
            $table->string('lembaga')->nullable();                 // asal lembaga/instansi pengajar (teks bebas)
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('umkm_access_granted_at')->nullable(); // waktu akses; role umkm disinkron model
            $table->timestamp('kontak_masuk_seen_at')->nullable();   // super admin: badge "pesan masuk baru sejak dilihat"
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Filter warga per nagari + status (juga index FK nagari_id, prefix kiri).
            // Peran di Spatie model_has_roles (multi-role), bukan kolom di sini.
            $table->index(['nagari_id', 'status'], 'users_nagari_status_idx');
        });

        // Keunikan username HANYA pada baris aktif — kolom komputasi (bukan pilihan
        // portabel lintas-driver, tapi proyek ini MySQL/MariaDB produksi; SQLite test
        // dapat CASE WHEN yang setara).
        $expression = DB::getDriverName() === 'sqlite'
            ? 'CASE WHEN deleted_at IS NULL THEN username ELSE NULL END'
            : 'IF(deleted_at IS NULL, username, NULL)';

        Schema::table('users', function (Blueprint $table) use ($expression) {
            $table->string('active_username')->nullable()->storedAs($expression);
            $table->unique('active_username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
