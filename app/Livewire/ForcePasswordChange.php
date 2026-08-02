<?php

namespace App\Livewire;

use App\Rules\NotInitialPassword;
use App\Services\FirstLoginPasswordService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Modal pemblokir ganti sandi untuk admin/pengguna yang masih memakai password awal default.
 * Sediakan nama lengkap wajib dan opsi ganti username untuk superadmin, pengajar,
 * dpmd, serta field wajib lembaga/instansi untuk pengajar.
 */
class ForcePasswordChange extends Component
{
    public string $name = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $lembaga = '';

    public bool $canChangeUsername = false;

    public bool $canSetName = false;

    public bool $canSetLembaga = false;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->hasAnyRole(['superadmin', 'pengajar', 'dpmd'])) {
            $this->canSetName = true;
            $this->name = (string) $user->name;
        }

        if ($this->canSetName && $user->penduduk_id === null) {
            $this->canChangeUsername = true;
            $this->username = (string) $user->username;
        }

        if ($user?->hasRole('pengajar')) {
            $this->canSetLembaga = true;
            $this->lembaga = (string) ($user->lembaga ?? '');
        }
    }

    public function save(FirstLoginPasswordService $firstLoginPassword): void
    {
        $user = auth()->user();
        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed', new NotInitialPassword],
        ];

        if ($this->canSetName) {
            $rules['name'] = ['required', 'string', 'min:2', 'max:255'];
        }

        if ($this->canChangeUsername) {
            $rules['username'] = [
                'nullable',
                'string',
                'min:3',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ];
        }

        if ($this->canSetLembaga) {
            $rules['lembaga'] = ['required', 'string', 'max:255'];
        }

        $data = $this->validate($rules);

        $updatedUser = $firstLoginPassword->change(
            user: $user,
            newPassword: $data['password'],
            newUsername: $this->canChangeUsername ? ($data['username'] ?? null) : null,
            newLembaga: $this->canSetLembaga ? ($data['lembaga'] ?? null) : null,
            newName: $this->canSetName ? ($data['name'] ?? null) : null,
        );

        if ($updatedUser === null) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            session()->flash('error', 'Password awal sudah diganti oleh sesi lain. Masuk kembali dengan password baru.');
            $this->redirect(route('login'));

            return;
        }

        // Segarkan hash sandi di sesi ini agar pengguna tetap login.
        session()->put('password_hash_'.Auth::getDefaultDriver(), $updatedUser->getAuthPassword());

        Notification::make()->title('Kata sandi dan profil berhasil diperbarui.')->success()->send();

        $this->redirect(Filament::getUrl());
    }

    public function render()
    {
        return view('filament.force-password-change');
    }
}
