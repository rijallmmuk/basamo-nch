<?php

namespace App\Livewire;

use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Modal pemblokir ganti sandi untuk admin yang masih memakai sandi awal (OTP).
 * Dirender lewat render hook panel (BODY_END) saat `must_change_password`, menutupi
 * layar sampai sandi diganti. No. HP & email opsional (TIDAK wajib).
 */
class ForcePasswordChange extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public string $phone = '';

    public string $email = '';

    public function save(): void
    {
        $data = $this->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
        ]);

        $user = auth()->user();
        $attributes = ['password' => $data['password']];

        // Lengkapi kontak hanya bila diisi (keduanya opsional).
        if (filled($this->phone)) {
            $attributes['phone'] = PhoneNumber::normalize($this->phone);
        }

        if (filled($this->email)) {
            $attributes['email'] = Str::lower(trim($this->email));
        }

        // Hook User::saving menghapus initial_otp & melepas must_change_password.
        $user->forceFill($attributes)->save();

        $this->redirect(route('filament.admin.pages.dashboard'));
    }

    public function render()
    {
        return view('filament.force-password-change');
    }
}
