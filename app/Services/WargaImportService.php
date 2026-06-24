<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Pekerjaan;
use App\Models\StatusPerkawinan;
use App\Models\User;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Membuat satu warga (akun `users` + identitas `penduduk`) dari satu baris impor Excel.
 *
 * Keamanan & best-practice:
 * - Desa selalu ditentukan server-side: desa_admin dipaksa ke desanya (kolom "Desa"
 *   pada file diabaikan); super_admin memetakan kolom "Desa" ke desa nyata by-nama.
 * - Wilayah wajib milik desa tersebut (cegah bocor lintas-desa).
 * - NIK divalidasi 16 digit, unik di DB, dan unik dalam satu file (lewat $seenNik).
 * - Penulisan akun + penduduk dibungkus transaksi → tak ada akun yatim tanpa identitas.
 * - Tiap kegagalan dilempar sebagai {@see RuntimeException} berpesan ramah (Indonesia)
 *   agar pemanggil bisa melaporkan per-baris tanpa menggagalkan seluruh impor.
 */
class WargaImportService
{
    public function __construct(private PendudukService $penduduk) {}

    /**
     * @param  array<string, mixed>  $row  baris ber-heading (key sudah ter-slug oleh WithHeadingRow)
     * @param  array<string, true>  $seenNik  NIK yang sudah diproses pada file ini (diteruskan by-ref)
     *
     * @throws RuntimeException bila baris tidak valid
     */
    public function createFromRow(array $row, User $actor, array &$seenNik): User
    {
        $val = fn (string $key): string => trim((string) ($row[$key] ?? ''));

        $nama = $val('nama');
        if ($nama === '') {
            throw new RuntimeException('Kolom "Nama" wajib diisi.');
        }

        $nik = preg_replace('/\D/', '', $val('nik')) ?? '';
        if (! preg_match('/^\d{16}$/', $nik)) {
            throw new RuntimeException('"NIK" harus 16 digit angka.');
        }
        if (isset($seenNik[$nik])) {
            throw new RuntimeException("NIK {$nik} duplikat di dalam file.");
        }
        if (User::withTrashed()->where('nik', $nik)->exists()) {
            throw new RuntimeException("NIK {$nik} sudah terdaftar.");
        }

        [$desaId, $desaNama] = $this->resolveDesa($val, $actor);

        // Judul kolom sub-unit pada template mengikuti sebutan desa (mis. "Jorong"),
        // jadi key barisnya ikut ter-slug ("jorong"). Fallback ke "wilayah".
        $sebutan = $actor->desa?->jenisSubUnit?->nama ?: 'Wilayah';
        $wilayah = $this->wilayahValue($row, $sebutan);

        if ($wilayah === '') {
            throw new RuntimeException("Kolom \"{$sebutan}\" wajib diisi.");
        }

        $unit = DesaUnit::where('desa_id', $desaId)
            ->whereRaw('LOWER(nama) = ?', [Str::lower($wilayah)])
            ->first();
        if (! $unit) {
            throw new RuntimeException("\"{$wilayah}\" bukan {$sebutan} terdaftar di desa {$desaNama}.");
        }

        $jenisKelamin = $this->resolveJenisKelamin($val('jenis_kelamin'));

        $tempatLahir = $val('tempat_lahir');
        if ($tempatLahir === '') {
            throw new RuntimeException('Kolom "Tempat Lahir" wajib diisi.');
        }

        $tanggalLahir = $this->resolveTanggalLahir($row['tanggal_lahir'] ?? null);

        $agamaId = $this->resolveLookup(Agama::class, $val('agama'), 'Agama');
        $statusKawinId = $this->resolveLookup(StatusPerkawinan::class, $val('status_perkawinan'), 'Status Perkawinan');
        $pekerjaanId = $this->resolveLookup(Pekerjaan::class, $val('pekerjaan'), 'Pekerjaan');

        $email = $this->resolveEmail($val('email'));
        $phone = PhoneNumber::normalize($val('no_hp')) ?: null;
        $status = $this->resolveStatus($val('status'));

        $user = DB::transaction(function () use ($nama, $nik, $email, $phone, $desaId, $unit, $status, $tempatLahir, $tanggalLahir, $jenisKelamin, $agamaId, $statusKawinId, $pekerjaanId): User {
            // Sandi acak tak terpakai; OTP login diterbitkan terpisah lewat aksi "Reset OTP".
            $user = User::create([
                'name' => $nama,
                'nik' => $nik,
                'email' => $email,
                'phone' => $phone,
                'desa_id' => $desaId,
                'desa_unit_id' => $unit->id,
                'role' => 'warga',
                'status' => $status,
                'password' => Str::random(40),
                'must_change_password' => true,
                'initial_otp' => null,
            ]);

            $this->penduduk->syncForUser($user->refresh(), [
                'tempat_lahir' => $tempatLahir,
                'tanggal_lahir' => $tanggalLahir->toDateString(),
                'jenis_kelamin' => $jenisKelamin,
                'agama_id' => $agamaId,
                'status_perkawinan_id' => $statusKawinId,
                'pekerjaan_id' => $pekerjaanId,
            ]);

            return $user;
        });

        $seenNik[$nik] = true;

        return $user;
    }

    /**
     * @param  callable(string): string  $val
     * @return array{0:int,1:string} [desa_id, nama desa]
     */
    private function resolveDesa(callable $val, User $actor): array
    {
        if ($actor->isDesaAdmin()) {
            return [$actor->desa_id, $actor->desa?->nama ?? 'desa Anda'];
        }

        $nama = $val('desa');
        if ($nama === '') {
            throw new RuntimeException('Kolom "Desa" wajib diisi.');
        }

        $desa = Desa::whereRaw('LOWER(nama) = ?', [Str::lower($nama)])->first();
        if (! $desa) {
            throw new RuntimeException("Desa \"{$nama}\" tidak ditemukan.");
        }

        return [$desa->id, $desa->nama];
    }

    /**
     * Ambil nilai sub-unit dari baris: utamakan key sesuai sebutan desa (judul kolom
     * template, mis. "jorong"), lalu fallback "wilayah".
     *
     * @param  array<string, mixed>  $row
     */
    private function wilayahValue(array $row, string $sebutan): string
    {
        foreach ([Str::slug($sebutan, '_'), 'wilayah'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function resolveJenisKelamin(string $value): string
    {
        $key = Str::lower(str_replace([' ', '-'], '', $value));

        return match ($key) {
            'l', 'lakilaki', 'laki', 'pria', 'male' => 'L',
            'p', 'perempuan', 'wanita', 'female' => 'P',
            default => throw new RuntimeException('"Jenis Kelamin" harus "Laki-laki" atau "Perempuan".'),
        };
    }

    /**
     * @param  class-string<Model>  $model  model referensi ber-kolom `nama` & `aktif`
     */
    private function resolveLookup(string $model, string $value, string $label): int
    {
        if ($value === '') {
            throw new RuntimeException("Kolom \"{$label}\" wajib diisi.");
        }

        $id = $model::query()
            ->where('aktif', true)
            ->whereRaw('LOWER(nama) = ?', [Str::lower($value)])
            ->value('id');

        if (! $id) {
            throw new RuntimeException("\"{$label}\" \"{$value}\" tidak dikenali — lihat sheet \"Referensi\".");
        }

        return (int) $id;
    }

    private function resolveEmail(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Email \"{$value}\" tidak valid.");
        }

        if (User::withTrashed()->where('email', $value)->exists()) {
            throw new RuntimeException("Email \"{$value}\" sudah dipakai.");
        }

        return $value;
    }

    private function resolveStatus(string $value): string
    {
        if ($value === '') {
            return ActiveStatus::Active->value;
        }

        return match (Str::lower($value)) {
            'aktif', 'active' => ActiveStatus::Active->value,
            'nonaktif', 'non-aktif', 'tidak aktif', 'inactive' => ActiveStatus::Inactive->value,
            default => throw new RuntimeException('"Status" harus "Aktif" atau "Nonaktif".'),
        };
    }

    private function resolveTanggalLahir(mixed $raw): CarbonImmutable
    {
        if ($raw === null || trim((string) $raw) === '') {
            throw new RuntimeException('Kolom "Tanggal Lahir" wajib diisi.');
        }

        if (is_numeric($raw)) {
            // Sel bertipe tanggal Excel → angka serial.
            try {
                $date = CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $raw))->startOfDay();
            } catch (\Throwable) {
                throw new RuntimeException('Format "Tanggal Lahir" tidak dikenali — pakai d/m/yyyy (mis. 17/05/1990).');
            }
        } else {
            $date = $this->parseDateText(trim((string) $raw));
        }

        if ($date->isFuture()) {
            throw new RuntimeException('"Tanggal Lahir" tidak boleh di masa depan — periksa tahunnya (format d/m/yyyy).');
        }

        return $date;
    }

    /**
     * Parse teks tanggal secara ketat. Pakai DateTimeImmutable (return false, bukan
     * throw seperti Carbon) + cek warning agar tahun overflow/ambigu (mis. 2-digit)
     * ditolak. Format utama d/m/Y (sesuai template); toleransi d-m-Y & YYYY-MM-DD.
     */
    private function parseDateText(string $text): CarbonImmutable
    {
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!'.$format, $text);
            $errors = \DateTimeImmutable::getLastErrors();

            $clean = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

            if ($parsed !== false && $clean) {
                return CarbonImmutable::instance($parsed)->startOfDay();
            }
        }

        throw new RuntimeException('Format "Tanggal Lahir" tidak dikenali — pakai d/m/yyyy (mis. 17/05/1990).');
    }
}
