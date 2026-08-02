<?php

namespace App\Services;

use App\Models\Penduduk;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Satu pintu penulisan identitas warga beserta akun login wajibnya. */
class WargaProvisioningService
{
    private ?Role $roleWarga = null;

    /** @var list<string> */
    private const IDENTITY_FIELDS = [
        'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
        'agama_id', 'pendidikan_id', 'status_perkawinan_id', 'pekerjaan_id',
    ];

    /** @var list<string> */
    private const ACCOUNT_FIELDS = ['email', 'phone', 'status'];

    public function __construct(private InitialPasswordService $initialPassword) {}

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * @param  array<string, mixed>  $data
     * @param  bool  $muatRelasi  false saat impor massal: pemanggil tak memakai
     *                            relasi `user`, sehingga kueri muatnya percuma.
     */
    public function create(array $data, int $nagariId, bool $muatRelasi = true, bool $tundaPeran = false): Penduduk
    {
        $initialPassword = $this->initialPassword->forRole('warga');
        [$identity, $account] = $this->normalizedData($data, $nagariId, forCreate: true);

        return DB::transaction(function () use ($identity, $account, $initialPassword, $muatRelasi, $tundaPeran): Penduduk {
            $penduduk = Penduduk::create($identity);
            $user = $this->createAccount($penduduk, $account, $initialPassword, $tundaPeran);

            // Sematkan tanpa kueri: pemanggil impor butuh id akunnya untuk
            // memasang peran secara massal di akhir bongkahan.
            $penduduk->setRelation('user', $user);

            return $muatRelasi ? $penduduk->load('user') : $penduduk;
        });
    }

    /**
     * Pasang peran warga untuk banyak akun sekaligus. Dipakai impor massal:
     * `assignRole` per akun menimbulkan dua kueri per baris, sedangkan di sini
     * seluruh bongkahan cukup satu penulisan.
     *
     * @param  list<int>  $userIds
     */
    public function assignRoleWargaMassal(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $roleId = $this->roleWarga()->getKey();

        DB::table('model_has_roles')->insertOrIgnore(
            collect($userIds)
                ->map(fn (int $id): array => [
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $id,
                ])
                ->all()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Penduduk $penduduk, array $data): Penduduk
    {
        [$identity, $account] = $this->normalizedData($data, $penduduk->nagari_id);

        return DB::transaction(function () use ($penduduk, $identity, $account): Penduduk {
            $lockedPenduduk = Penduduk::query()->lockForUpdate()->findOrFail($penduduk->getKey());
            $lockedPenduduk->update($identity);

            $user = $lockedPenduduk->user;

            if ($user === null) {
                $this->createAccount($lockedPenduduk, $account);
            } else {
                $user->update([
                    ...$account,
                    'name' => $lockedPenduduk->nama,
                    'nik' => $lockedPenduduk->nik,
                    'nagari_id' => $lockedPenduduk->nagari_id,
                ]);

                if (! $user->hasRole('warga')) {
                    $user->assignRole(Role::findOrCreate('warga', 'web'));
                }
            }

            return $lockedPenduduk->refresh()->load('user');
        });
    }

    /**
     * Mempersiapkan data untuk sisipan massal (bulk insert) tanpa menuliskannya ke DB.
     * Mengembalikan identitas dan akun lengkap dengan timestamp yang diperlukan.
     *
     * @param  array<string, mixed>  $data
     * @return array{identity: array<string, mixed>, account: array<string, mixed>}
     */
    public function prepareForBulk(array $data, int $nagariId): array
    {
        [$identity, $account] = $this->normalizedData($data, $nagariId, forCreate: true);
        $now = now()->toDateTimeString();

        $identity['created_at'] = $now;
        $identity['updated_at'] = $now;

        $account['name'] = $identity['nama'];
        $account['nik'] = $identity['nik'];
        $account['nagari_id'] = $identity['nagari_id'];
        $account['must_change_password'] = true;
        // Password akan di-hash di bulkInsert
        $account['created_at'] = $now;
        $account['updated_at'] = $now;

        return [
            'identity' => $identity,
            'account' => $account,
        ];
    }

    /**
     * Menyisipkan identitas dan akun dalam jumlah besar sekaligus menggunakan batch query,
     * secara drastis mengurangi waktu pemrosesan impor Excel.
     *
     * @param  list<array<string, mixed>>  $identities
     * @param  list<array<string, mixed>>  $accounts
     * @return list<int> Daftar ID akun yang berhasil dibuat
     */
    public function bulkInsert(array $identities, array $accounts): array
    {
        if ($identities === []) {
            return [];
        }

        DB::transaction(function () use ($identities, &$accounts): void {
            Penduduk::insert($identities);

            $niks = array_column($identities, 'nik');
            $penduduks = Penduduk::whereIn('nik', $niks)->pluck('id', 'nik');

            $hashedPassword = \Illuminate\Support\Facades\Hash::make($this->initialPassword->forRole('warga'));

            foreach ($accounts as &$account) {
                $account['penduduk_id'] = $penduduks[$account['nik']] ?? null;
                $account['password'] = $hashedPassword;
            }

            // Hapus yang penduduknya tidak ditemukan (seharusnya tidak mungkin karena transaksi)
            $validAccounts = array_filter($accounts, fn ($a): bool => $a['penduduk_id'] !== null);

            User::insert($validAccounts);
        });

        $niks = array_column($identities, 'nik');
        return User::whereIn('nik', $niks)->pluck('id')->all();
    }

    /** Role warga dicari sekali per proses, bukan sekali per baris impor. */
    private function roleWarga(): Role
    {
        return $this->roleWarga ??= Role::findOrCreate('warga', 'web');
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function createAccount(Penduduk $penduduk, array $account, ?string $initialPassword = null, bool $tundaPeran = false): User
    {
        $user = User::create([
            ...$account,
            'name' => $penduduk->nama,
            'nik' => $penduduk->nik,
            'penduduk_id' => $penduduk->id,
            'nagari_id' => $penduduk->nagari_id,
            'password' => $initialPassword ?? $this->initialPassword->forRole('warga'),
            'must_change_password' => true,
        ]);

        // Impor massal memasang peran belakangan lewat assignRoleWargaMassal().
        if (! $tundaPeran) {
            $user->assignRole($this->roleWarga());
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function normalizedData(array $data, int $nagariId, bool $forCreate = false): array
    {
        if (! array_key_exists('nama', $data) && array_key_exists('name', $data)) {
            $data['nama'] = $data['name'];
        }

        $identity = Arr::only($data, self::IDENTITY_FIELDS);
        $identity['nagari_id'] = $nagariId;

        $account = Arr::only($data, self::ACCOUNT_FIELDS);

        if (array_key_exists('email', $account)) {
            $account['email'] = filled($account['email'])
                ? Str::lower(trim((string) $account['email']))
                : null;
        }

        if (array_key_exists('phone', $account)) {
            $account['phone'] = PhoneNumber::normalize($account['phone']);
        }

        if ($forCreate) {
            $account['status'] ??= 'active';
        }

        return [$identity, $account];
    }
}
