<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arsitektur 3 lapisan untuk data warga (pisahkan 3 concern):
 *
 *  1. Identitas → tabel `penduduk` (siapa orangnya): NIK, nama, demografi,
 *     nagari. Seseorang bisa terdaftar di sini TANPA punya akun.
 *  2. Akun      → tabel `users` (login + aktivitas LMS/UMKM/XP), tertaut via
 *     `users.penduduk_id` yang UNIK: maksimal satu akun per orang, supaya progres
 *     belajar tidak terpecah ke beberapa akun. superadmin sistem boleh tanpa
 *     penduduk (kolomnya nullable, dan unique mengizinkan banyak NULL).
 *  3. Akses     → Spatie multi-role. Lima role tetap, tanpa CRUD peran di panel.
 *
 * NIK kanonik ada di `penduduk`; di-mirror ke `users.nik` (dibuat di migrasi users,
 * FK penduduk_id ditunda ke sini karena `penduduk` dibuat setelah `users`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penduduk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->nullable()->constrained('nagaris')->nullOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('nama', 255);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable(); // L / P
            $table->foreignId('agama_id')->nullable()->constrained('agama')->nullOnDelete();
            $table->foreignId('pendidikan_id')->nullable()->constrained('pendidikan')->nullOnDelete();
            $table->foreignId('status_perkawinan_id')->nullable()->constrained('status_perkawinan')->nullOnDelete();
            $table->foreignId('pekerjaan_id')->nullable()->constrained('pekerjaan')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('nagari_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('penduduk_id', 'users_penduduk_id_unique');
            // restrictOnDelete: identitas warga tidak boleh dihapus selagi akunnya masih
            // ada — cegah akun yatim yang kehilangan seluruh data kependudukannya.
            $table->foreign('penduduk_id')->references('id')->on('penduduk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['penduduk_id']);
            $table->dropUnique('users_penduduk_id_unique');
        });

        Schema::dropIfExists('penduduk');
    }
};
