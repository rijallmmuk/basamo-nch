<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Setel ulang kata sandi satu akun dari baris perintah.
 *
 * Ada karena ketiadaannya pernah merusak produksi. Sandi super admin hanya
 * tercetak sekali saat seeding, dan ketika baris itu terlewat, jalan keluar yang
 * paling kelihatan adalah menyunting kolom `password` lewat phpMyAdmin. Sandi
 * yang diketik di sana tersimpan APA ADANYA sebagai teks biasa, bukan hash, dan
 * sejak itu setiap percobaan masuk ke akun tersebut dibalas galat 500.
 *
 * Perintah ini menutup jalan itu: sandinya dibangkitkan acak, di-hash dengan
 * hasher aplikasi, dan penanda wajib-ganti dinyalakan.
 */
#[Signature('ops:reset-password {identitas? : Username atau NIK akun} {--audit : Hanya periksa, tidak mengubah apa pun}')]
#[Description('Setel ulang kata sandi satu akun, atau periksa akun yang hash-nya rusak')]
class ResetPasswordCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('audit')) {
            return $this->audit();
        }

        $identitas = $this->argument('identitas') ?: $this->ask('Username atau NIK akun');

        if (blank($identitas)) {
            $this->error('Identitas wajib diisi.');

            return self::FAILURE;
        }

        // Bentuk identitas mengikuti halaman masuk: 16 digit dibaca sebagai NIK
        // warga, selain itu sebagai username. Surel BUKAN identitas login, jadi
        // sengaja tidak dicari di sini agar perilakunya tidak berbeda.
        $kolom = ctype_digit((string) $identitas) && strlen((string) $identitas) === 16
            ? 'nik'
            : 'username';

        $user = User::query()->where($kolom, $identitas)->first();

        if (! $user) {
            $this->error("Akun dengan {$kolom} \"{$identitas}\" tidak ditemukan.");
            $this->line('Surel tidak bisa dipakai di sini, sama seperti di halaman masuk.');

            return self::FAILURE;
        }

        $sandi = Str::password(20, symbols: false);

        $user->forceFill([
            'password' => Hash::make($sandi),
            'must_change_password' => true,
        ])->save();

        $this->newLine();
        $this->info("Kata sandi {$user->name} ({$kolom}: {$identitas}) sudah disetel ulang.");
        $this->newLine();
        $this->warn("  Sandi sementara: {$sandi}");
        $this->newLine();
        $this->line('Catat sekarang, tidak ditampilkan lagi. Wajib diganti saat login pertama.');
        $this->line('Masuk lewat /login memakai '.($kolom === 'nik' ? 'NIK' : 'username').', bukan surel.');

        return self::SUCCESS;
    }

    /**
     * Cari baris yang isinya bukan hash bcrypt yang sah.
     *
     * Panjang 60 ikut diperiksa, bukan hanya awalannya: hash bcrypt yang terpotong
     * kolom tetap berawalan `$2y$` tetapi tidak akan pernah cocok, dan gejalanya
     * sama membingungkannya dengan teks biasa.
     */
    private function audit(): int
    {
        $rusak = DB::table('users')
            ->select('id', 'username', 'nik', 'name')
            ->selectRaw('COALESCE(LEFT(password, 4), "(NULL)") AS awalan')
            ->selectRaw('COALESCE(LENGTH(password), 0) AS panjang')
            ->where(fn ($query) => $query
                ->whereNull('password')
                ->orWhere('password', 'not like', '$2y$%')
                ->orWhereRaw('LENGTH(password) <> 60'))
            ->orderBy('id')
            ->get();

        $total = DB::table('users')->count();

        if ($rusak->isEmpty()) {
            $this->info("Seluruh {$total} akun memakai hash bcrypt yang sah.");

            return self::SUCCESS;
        }

        $this->error("{$rusak->count()} dari {$total} akun punya hash tidak sah:");
        $this->table(
            ['ID', 'Username', 'NIK', 'Nama', 'Awalan', 'Panjang'],
            $rusak->map(fn ($baris): array => [
                $baris->id,
                $baris->username ?? '-',
                $baris->nik ?? '-',
                $baris->name,
                $baris->awalan,
                $baris->panjang,
            ])->all(),
        );
        $this->newLine();
        $this->line('Akun di atas TIDAK BISA masuk sama sekali. Perbaiki satu per satu:');
        $this->line('  php artisan ops:reset-password <username-atau-nik>');

        return self::FAILURE;
    }
}
