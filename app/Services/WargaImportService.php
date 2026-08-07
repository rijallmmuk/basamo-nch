<?php

namespace App\Services;

use App\Enums\JenisKelamin;
use App\Models\Agama;
use App\Models\Nagari;
use App\Models\Pekerjaan;
use App\Models\Pendidikan;
use App\Models\Penduduk;
use App\Models\StatusPerkawinan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Membuat satu warga (akun `users` + identitas `penduduk`) dari satu baris impor Excel.
 *
 * Kolom rujukan (agama_id, pendidikan_id, pekerjaan_id, status_kawin_id) menerima ID
 * mentah — SENGAJA disamakan dengan skema export penduduk OpenSID milik nagari (tabel
 * referensi kita diselaraskan persis ID-nya, lihat migrasi `create_warga_reference_
 * tables`), supaya file ekspor mentah nagari (44 kolom, banyak tak relevan) bisa langsung
 * diunggah tanpa diubah — kolom yang tak dikenal cukup diabaikan.
 *
 * Kolom yang sama juga menerima NAMA pilihan, karena {@see WargaTemplateBuilder} kini
 * memasang dropdown berisi nama supaya pengisi tidak perlu menghafal arti angka.
 *
 * Keamanan & best-practice:
 * - Nagari selalu ditentukan server-side: operator dipaksa ke nagarinya.
 * - NIK divalidasi 16 digit, unik di DB, dan unik dalam satu file (lewat $seenNik).
 * - Penulisan akun + penduduk dibungkus transaksi → tak ada akun yatim tanpa identitas.
 * - Tiap kegagalan dilempar sebagai {@see RuntimeException} berpesan ramah (Indonesia)
 *   agar pemanggil bisa melaporkan per-baris tanpa menggagalkan seluruh impor.
 */
class WargaImportService
{
    /**
     * Referensi yang sah, dimuat SEKALI per proses impor. Tanpa ini setiap baris
     * menambah empat kueri existence, dan pada berkas puluhan ribu baris itu saja
     * sudah puluhan ribu kueri sia-sia.
     *
     * @var array<class-string<Model>, array{id: array<int, true>, nama: array<string, int>}>|null
     */
    private ?array $referensi = null;

    public function __construct(
        private WargaProvisioningService $warga,
    ) {}

    /**
     * Memvalidasi satu baris warga dan mengembalikan data siap bulk-insert.
     *
     * @param  array<string, mixed>  $row  baris ber-heading (key sudah dinormalisasi menjadi slug)
     * @param  array<string, true>  $seenNik  NIK yang sudah diproses pada file ini (diteruskan by-ref)
     * @param  array<string, true>  $nikTerdaftar  NIK yang sudah ada di basis data, diperiksa
     *                                             sekali per bongkahan oleh pemanggil
     *
     * @throws RuntimeException bila baris tidak valid
     * @return array{identity: array<string, mixed>, account: array<string, mixed>}
     */
    public function prepareRow(array $row, Nagari $nagari, array &$seenNik, array $nikTerdaftar = []): array
    {
        $nama = trim($this->column($row, ['nama']));
        if ($nama === '') {
            throw new RuntimeException('Kolom "nama" wajib diisi.');
        }

        $nik = preg_replace('/\D/', '', $this->column($row, ['nik'])) ?? '';
        if (! preg_match('/^\d{16}$/', $nik)) {
            throw new RuntimeException('"nik" harus 16 digit angka.');
        }
        if (isset($seenNik[$nik])) {
            throw new RuntimeException("NIK {$nik} duplikat di dalam file.");
        }
        if (isset($nikTerdaftar[$nik])) {
            throw new RuntimeException("NIK {$nik} sudah terdaftar.");
        }

        $jenisKelamin = $this->resolveSex($this->column($row, ['sex', 'jenis_kelamin']));
        $tempatLahir = trim($this->column($row, ['tempatlahir', 'tempat_lahir'])) ?: null;
        $tanggalLahir = $this->resolveTanggalLahir($row['tanggallahir'] ?? $row['tanggal_lahir'] ?? null);

        $agamaId = $this->resolveId(Agama::class, $this->column($row, ['agama_id']), 'agama_id');
        $pendidikanId = $this->resolveId(Pendidikan::class, $this->column($row, ['pendidikan_id', 'pendidikan_kk_id']), 'pendidikan_id');
        $pekerjaanId = $this->resolveId(Pekerjaan::class, $this->column($row, ['pekerjaan_id']), 'pekerjaan_id');
        $statusKawinId = $this->resolveId(StatusPerkawinan::class, $this->column($row, ['status_kawin_id', 'status_kawin']), 'status_kawin_id');

        $seenNik[$nik] = true;

        return $this->warga->prepareForBulk([
            'nama' => $nama,
            'nik' => $nik,
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir?->toDateString(),
            'jenis_kelamin' => $jenisKelamin,
            'agama_id' => $agamaId,
            'pendidikan_id' => $pendidikanId,
            'status_perkawinan_id' => $statusKawinId,
            'pekerjaan_id' => $pekerjaanId,
        ], $nagari->id);
    }

    /**
     * Pasang peran warga untuk seluruh akun yang baru dibuat pada satu bongkahan.
     *
     * @param  list<int>  $userIds
     */
    public function pasangPeranWarga(array $userIds): void
    {
        $this->warga->assignRoleWargaMassal($userIds);
    }

    /**
     * NIK pada satu bongkahan yang SUDAH ada di basis data. Satu kueri untuk 500
     * baris, menggantikan satu kueri existence untuk tiap baris.
     *
     * @param  list<string>  $nikList
     * @return array<string, true>
     */
    public function nikTerdaftar(array $nikList): array
    {
        if ($nikList === []) {
            return [];
        }

        return Penduduk::withTrashed()
            ->whereIn('nik', $nikList)
            ->pluck('nik')
            ->flip()
            ->map(fn (): bool => true)
            ->all();
    }

    /**
     * Ambil nilai kolom pertama yang ada isinya dari daftar alias (toleran nama kolom
     * beda-beda antar sumber file — template kita sendiri vs. ekspor mentah OpenSID).
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function column(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function resolveSex(string $value): ?string
    {
        $key = mb_strtolower(str_replace([' ', '-'], '', $value));

        return match ($key) {
            '1', 'l', 'lakilaki', 'laki', 'pria', 'male' => JenisKelamin::LakiLaki->value,
            '2', 'p', 'perempuan', 'wanita', 'female' => JenisKelamin::Perempuan->value,
            '' => null,
            default => throw new RuntimeException('"sex" harus 1 (Laki-laki) atau 2 (Perempuan).'),
        };
    }

    /**
     * Menerima DUA bentuk isian, dan itu disengaja:
     *
     * - angka ID, karena ekspor mentah OpenSID memuatnya dan berkas lama yang sudah
     *   diisi ID tidak boleh mendadak ditolak;
     * - nama pilihan, karena template kita kini memakai dropdown berisi nama supaya
     *   pengisi tidak perlu menghafal arti angka.
     *
     * @param  class-string<Model>  $model  model referensi ber-kolom `id` & `aktif`
     */
    private function resolveId(string $model, string $value, string $label): ?int
    {
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            if (! isset($this->referensi()[$model]['id'][(int) $value])) {
                throw new RuntimeException("\"{$label}\" {$value} tidak dikenali. Lihat sheet \"Referensi\".");
            }

            return (int) $value;
        }

        $id = $this->referensi()[$model]['nama'][$this->samakanNama($value)] ?? null;

        if ($id === null) {
            throw new RuntimeException("\"{$label}\" \"{$value}\" tidak dikenali. Pilih dari dropdown template atau lihat sheet \"Referensi\".");
        }

        return $id;
    }

    /**
     * Beda huruf besar-kecil dan spasi ganda tidak boleh menggagalkan satu baris:
     * isian sering datang dari salin-tempel, bukan hanya dari dropdown.
     */
    private function samakanNama(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    /**
     * Referensi yang sah dalam dua bentuk pencarian: id dan nama. Dibaca sekali lalu
     * disimpan di memori — tabelnya kecil (puluhan baris), jadi aman dimuat penuh,
     * dan tanpa ini tiap baris impor menambah empat kueri.
     *
     * Baris aktif ditulis belakangan agar ia yang menang bila kelak ada nama kembar
     * antara baris aktif dan baris yang sudah dinonaktifkan.
     *
     * @return array<class-string<Model>, array{id: array<int, true>, nama: array<string, int>}>
     */
    private function referensi(): array
    {
        return $this->referensi ??= collect([Agama::class, Pendidikan::class, Pekerjaan::class, StatusPerkawinan::class])
            ->mapWithKeys(function (string $model): array {
                $rows = $model::query()->orderBy('aktif')->get(['id', 'nama']);

                return [$model => [
                    'id' => $rows->pluck('id')->flip()->map(fn (): bool => true)->all(),
                    'nama' => $rows->mapWithKeys(fn (Model $row): array => [
                        $this->samakanNama((string) $row->nama) => (int) $row->id,
                    ])->all(),
                ]];
            })
            ->all();
    }

    private function resolveTanggalLahir(mixed $raw): ?CarbonImmutable
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        if ($raw instanceof \DateTimeInterface) {
            $date = CarbonImmutable::instance($raw)->startOfDay();
        } elseif (is_numeric($raw)) {
            // Sel bertipe tanggal Excel → angka serial.
            try {
                $date = CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $raw))->startOfDay();
            } catch (\Throwable) {
                throw new RuntimeException('Format "tanggallahir" tidak dikenali. Pakai yyyy-mm-dd (mis. 1990-05-17).');
            }
        } else {
            $date = $this->parseDateText(trim((string) $raw));
        }

        if ($date->isFuture()) {
            throw new RuntimeException('"tanggallahir" tidak boleh di masa depan. Periksa tahunnya (format yyyy-mm-dd).');
        }

        return $date;
    }

    /**
     * Parse teks tanggal secara ketat. Pakai DateTimeImmutable (return false, bukan
     * throw seperti Carbon) + cek warning agar tahun overflow/ambigu (mis. 2-digit)
     * ditolak. Format utama Y-m-d (sesuai spesifikasi/template); toleransi d/m/Y & d-m-Y.
     */
    private function parseDateText(string $text): CarbonImmutable
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!'.$format, $text);
            $errors = \DateTimeImmutable::getLastErrors();

            $clean = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

            if ($parsed !== false && $clean) {
                return CarbonImmutable::instance($parsed)->startOfDay();
            }
        }

        throw new RuntimeException('Format "tanggallahir" tidak dikenali. Pakai yyyy-mm-dd (mis. 1990-05-17).');
    }
}
