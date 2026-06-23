<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Arsitektur 3 lapisan untuk data warga (pisahkan 3 concern):
 *
 *  1. Identitas → tabel `penduduk` (siapa orangnya): NIK, nama, demografi,
 *     desa & alamat. Seseorang bisa terdaftar di sini TANPA punya akun.
 *  2. Akun      → tabel `users` (login + aktivitas LMS/UMKM/XP), tertaut via
 *     `users.penduduk_id` (sengaja NON-UNIK → 1 orang boleh punya >1 akun, mis. akun
 *     warga untuk LMS + akun pejabat saat pengembangan jabatan kelak). super_admin
 *     sistem boleh tanpa penduduk.
 *  3. Akses     → Spatie role + Shield (sudah ada). 1 akun = 1 role.
 *
 * NIK kanonik ada di `penduduk`; di-mirror ke `users.nik` sebagai kunci login portal
 * (warga login pakai NIK) agar `Auth::attempt` tetap sederhana tanpa custom provider.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penduduk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->nullable()->constrained('desas')->nullOnDelete();
            $table->foreignId('desa_unit_id')->nullable()->constrained('desa_units')->nullOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('nama', 255);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable(); // L / P
            $table->foreignId('agama_id')->nullable()->constrained('agama')->nullOnDelete();
            $table->foreignId('status_perkawinan_id')->nullable()->constrained('status_perkawinan')->nullOnDelete();
            $table->foreignId('pekerjaan_id')->nullable()->constrained('pekerjaan')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('desa_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // NIK mirror (kunci login warga); kanonik di `penduduk`.
            $table->string('nik', 16)->nullable()->unique()->after('name');
            $table->foreignId('penduduk_id')->nullable()->after('nik')->constrained('penduduk')->nullOnDelete();
        });

        $this->migrateExistingWarga();
    }

    /**
     * DB dev lama: warga lama menyimpan NIK di `username`. Pindahkan ke `users.nik`,
     * buat baris `penduduk` dari datanya, lalu tautkan. Pada instalasi fresh (mis.
     * SQLite test) belum ada warga → no-op. Ditulis portabel (tanpa JOIN-update).
     */
    private function migrateExistingWarga(): void
    {
        DB::table('users')->where('role', 'warga')->orderBy('id')->get()->each(function ($user): void {
            $nik = $user->nik ?? $user->username;

            if (blank($nik)) {
                return;
            }

            $pendudukId = DB::table('penduduk')->insertGetId([
                'nik' => $nik,
                'nama' => $user->name,
                'desa_id' => $user->desa_id,
                'desa_unit_id' => $user->desa_unit_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update([
                'nik' => $nik,
                'username' => null,
                'penduduk_id' => $pendudukId,
            ]);
        });
    }

    public function down(): void
    {
        // Kembalikan NIK warga ke username sebelum kolom dilepas.
        DB::table('users')->where('role', 'warga')->whereNotNull('nik')->orderBy('id')->get()->each(function ($user): void {
            DB::table('users')->where('id', $user->id)->update(['username' => $user->nik]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penduduk_id');
            $table->dropColumn('nik');
        });

        Schema::dropIfExists('penduduk');
    }
};
