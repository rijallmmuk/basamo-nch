<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class BackofficeUserService
{
    /** @var list<string> */
    public const CORE_BACKOFFICE_ROLES = ['superadmin', 'operator', 'pengajar', 'dpmd'];

    /** @var list<string> */
    public const PORTAL_ROLES = ['warga'];

    /** Generate username unik: kata pertama dari nama + tiga digit acak. */
    public function generateUniqueUsername(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $kataPertama = Str::before($slug, '-');
        $base = Str::limit(filled($kataPertama) ? $kataPertama : 'user', 252, '');
        $angkaAwal = random_int(0, 999);

        // Memulai dari angka acak, lalu mengitari seluruh ruang 000–999. Dengan
        // begitu format selalu tepat tiga digit dan pencarian tetap pasti selesai
        // selama masih ada satu username yang tersedia untuk kata tersebut.
        for ($offset = 0; $offset < 1000; $offset++) {
            $angka = ($angkaAwal + $offset) % 1000;
            $username = $base.str_pad((string) $angka, 3, '0', STR_PAD_LEFT);

            $sudahDipakai = User::query()
                ->where('username', $username)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists();

            if (! $sudahDipakai) {
                return $username;
            }
        }

        throw ValidationException::withMessages([
            'username' => "Seluruh kombinasi username untuk {$base} sudah digunakan. Isi username secara manual.",
        ]);
    }

    /** @param array<string, mixed> $data @param list<string> $roles */
    public function create(array $data, array $roles): User
    {
        $roles = $this->validatedRoles($roles, allowOperator: false);

        if (blank($data['password'] ?? null)) {
            $primaryRole = $roles[0] ?? 'superadmin';
            $data['password'] = app(InitialPasswordService::class)->forRole($primaryRole);
        }

        if (blank($data['username'] ?? null) && filled($data['name'] ?? null)) {
            $data['username'] = $this->generateUniqueUsername((string) $data['name']);
        }

        return DB::transaction(function () use ($data, $roles): User {
            $this->ensureValidatedRolesExist($roles);
            $user = User::create($this->normalizedData($data) + [
                'must_change_password' => true,
            ]);
            $user->syncRoles($roles);

            return $user;
        });
    }

    /** @param array<string, mixed> $data @param list<string> $roles */
    public function update(User $user, array $data, array $roles): User
    {
        return DB::transaction(function () use ($user, $data, $roles): User {
            $isProvisionedOperator = $user->hasRole('operator');

            if ($isProvisionedOperator) {
                $roles = array_values(array_unique([...$roles, 'operator']));
            }

            $roles = $this->validatedRoles(
                $roles,
                allowOperator: $isProvisionedOperator,
                required: $user->penduduk_id === null,
            );

            if ($user->is(auth()->user())) {
                if (($data['status'] ?? $user->status->value) !== ActiveStatus::Active->value) {
                    throw ValidationException::withMessages([
                        'status' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.',
                    ]);
                }

                if ($user->isSuperAdmin() && ! in_array('superadmin', $roles, true)) {
                    throw ValidationException::withMessages([
                        'role_names' => 'Role superadmin pada akun yang sedang digunakan tidak dapat dicabut.',
                    ]);
                }
            }

            if ($isProvisionedOperator) {
                unset($data['nagari_id'], $data['username']);
            }

            if ($user->penduduk_id !== null) {
                unset($data['name'], $data['nagari_id'], $data['username']);
            }

            $portalRoles = $user->getRoleNames()
                ->filter(fn (string $role): bool => in_array($role, self::PORTAL_ROLES, true))
                ->all();

            $this->ensureValidatedRolesExist([...$portalRoles, ...$roles]);
            $user->update($this->normalizedData($data, $user->id));
            $user->syncRoles(array_values(array_unique([...$portalRoles, ...$roles])));

            return $user;
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizedData(array $data, ?int $userId = null): array
    {
        $data = Arr::only($data, ['name', 'username', 'email', 'phone', 'lembaga', 'password', 'nagari_id', 'status', 'must_change_password']);
        $data['email'] = filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null;
        $data['phone'] = PhoneNumber::normalize($data['phone'] ?? null);

        if (blank($data['username'] ?? null) && filled($data['name'] ?? null)) {
            $data['username'] = $this->generateUniqueUsername((string) $data['name'], $userId);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    /** @param list<string> $roles @return list<string> */
    private function validatedRoles(array $roles, bool $allowOperator, bool $required = true): array
    {
        $roles = array_values(array_unique(array_filter($roles)));
        $allowed = $allowOperator
            ? self::CORE_BACKOFFICE_ROLES
            : array_values(array_diff(self::CORE_BACKOFFICE_ROLES, ['operator']));
        $hasCoreAnchor = collect($roles)->contains(
            fn (string $role): bool => in_array($role, self::CORE_BACKOFFICE_ROLES, true),
        );
        $coreRoleCount = collect($roles)
            ->filter(fn (string $role): bool => in_array($role, self::CORE_BACKOFFICE_ROLES, true))
            ->count();

        if (($required && $roles === [])
            || array_diff($roles, $allowed) !== []
            || ($roles !== [] && ! $hasCoreAnchor)
            || $coreRoleCount > 1) {
            throw ValidationException::withMessages([
                'role_names' => 'Pilih tepat satu role back-office.',
            ]);
        }

        return $roles;
    }

    /** @param list<string> $roles */
    private function ensureValidatedRolesExist(array $roles): void
    {
        foreach ($roles as $role) {
            if (in_array($role, User::ROLE_PRIORITY, true)) {
                Role::findOrCreate($role, 'web');
            }
        }
    }
}
